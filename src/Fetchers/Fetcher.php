<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015 to present Leon Jacobs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace Seat\Eseye\Fetchers;

use GuzzleHttp\Psr7\Uri;
use Jose\Component\Checker\InvalidClaimException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Seat\Eseye\Checker\EsiTokenValidator;
use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\Eseye;
use Seat\Eseye\Exceptions\InvalidAuthenticationException;
use Seat\Eseye\Exceptions\RequestFailedException;

class Fetcher implements FetcherInterface
{
    /**
     * @var \Seat\Eseye\Containers\EsiAuthentication|null
     */
    protected ?EsiAuthentication $authentication;

    /**
     * @var \Psr\Http\Client\ClientInterface
     */
    protected ClientInterface $client;

    /**
     * @var \Seat\Eseye\Checker\EsiTokenValidator
     */
    protected EsiTokenValidator $jwt_validator;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var string
     */
    protected string $sso_base;

    /**
     * @var \Psr\Http\Message\RequestFactoryInterface
     */
    private RequestFactoryInterface $request_factory;

    /**
     * @var \Psr\Http\Message\StreamFactoryInterface
     */
    private StreamFactoryInterface $stream_factory;

    /**
     * EseyeFetcher constructor.
     *
     * @param  \Seat\Eseye\Containers\EsiAuthentication|null  $authentication
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function __construct(EsiAuthentication $authentication = null)
    {
        $this->authentication = $authentication;

        // Init the logger
        $this->logger = Configuration::getInstance()->getLogger();

        // Init the HTTP client
        $this->client = Configuration::getInstance()->getHttpClient();
        $this->stream_factory = Configuration::getInstance()->getHttpStreamFactory();
        $this->request_factory = Configuration::getInstance()->getHttpRequestFactory();

        // Init SSO base URI
        $this->sso_base = sprintf('%s://%s:%d/v2/oauth',
            Configuration::getInstance()->sso_scheme,
            Configuration::getInstance()->sso_host,
            Configuration::getInstance()->sso_port);

        // Init JWT validator
        $this->jwt_validator = new EsiTokenValidator();
    }

    /**
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $body
     * @param  array  $headers
     * @return \Seat\Eseye\Containers\EsiResponse
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\DiscoverServiceNotAvailableException
     */
    public function call(string $method, string $uri, array $body, array $headers = []): EsiResponse
    {
        // If we have authentication data, add the
        // Authorization header.
        if ($this->getAuthentication())
            $headers = array_merge($headers, [
                'Authorization' => $this->getBearerAuthorizationHeader(),
            ]);

        return $this->httpRequest($method, $uri, $headers, $body);
    }

    /**
     * @return \Seat\Eseye\Containers\EsiAuthentication|null
     */
    public function getAuthentication(): EsiAuthentication|null
    {
        return $this->authentication;
    }

    /**
     * @param  \Seat\Eseye\Containers\EsiAuthentication  $authentication
     *
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     */
    public function setAuthentication(EsiAuthentication $authentication): void
    {
        if (! $authentication->valid())
            throw new InvalidAuthenticationException('Authentication data invalid/empty');

        $this->authentication = $authentication;
    }

    /**
     * @return string
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\DiscoverServiceNotAvailableException
     */
    private function getToken(): string
    {
        // Ensure that we have authentication data before we try
        // and get a token.
        if (! $this->getAuthentication())
            throw new InvalidAuthenticationException(
                'Trying to get a token without authentication data.');

        // Check the expiry date.
        $expires = carbon($this->getAuthentication()->token_expires);

        // If the token expires in the next minute, refresh it.
        if ($expires->lte(carbon('now')->addMinute()))
            $this->refreshToken();

        return $this->getAuthentication()->access_token;
    }

    /**
     * Refresh the Access token that we have in the EsiAccess container.
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\DiscoverServiceNotAvailableException
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     */
    private function refreshToken(): void
    {
        // Make the post request for a new access_token
        $stream = $this->stream_factory->createStream($this->getRefreshTokenForm());

        $request = $this->request_factory->createRequest('POST', $this->sso_base . '/token')
            ->withHeader('Authorization', $this->getBasicAuthorizationHeader())
            ->withHeader('User-Agent', 'Eseye/' . Eseye::VERSION . '/' . Configuration::getInstance()->http_user_agent)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($stream);

        $response = $this->getClient()->sendRequest($request);

        // Grab the body from the StreamInterface instance.
        $content = $response->getBody()->getContents();

        // Client or Server Exception
        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 600) {
            // Log the event as failed
            $this->logger->error('[http ' . $response->getStatusCode() . ', ' .
                strtolower($response->getReasonPhrase()) . '] ' .
                'get -> ' . $this->sso_base . '/token'
            );

            // For debugging purposes, log the response body
            $this->logger->debug('Request for get -> ' . $this->sso_base . '/token failed. Response body was: ' .
                $content);

            // Raise the exception that should be handled by the caller
            throw new RequestFailedException($this->makeEsiResponse(
                $content,
                $response->getHeaders(),
                'now',
                $response->getStatusCode())
            );
        }

        $json = json_decode($content);

        // Get the current EsiAuth container
        $authentication = $this->getAuthentication();

        $claims = $this->jwt_validator->validateToken($authentication->client_id, $json->access_token);
        $this->logger->debug('Successfully validate delivered token', [
            'claims' => $claims,
        ]);

        // Set the new authentication values from the request
        $authentication->access_token = $json->access_token;
        $authentication->refresh_token = $json->refresh_token;
        $authentication->token_expires = $claims['exp'];
        $authentication->scopes = $claims['scp'];

        // ... and update the container
        $this->setAuthentication($authentication);
    }

    /**
     * @return string
     */
    private function getBasicAuthorizationHeader(): string
    {
        return 'Basic ' . base64_encode($this->authentication->client_id . ':' . $this->authentication->secret);
    }

    /**
     * @return string
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Seat\Eseye\Exceptions\DiscoverServiceNotAvailableException
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     */
    private function getBearerAuthorizationHeader(): string
    {
        return 'Bearer ' . $this->getToken();
    }

    /**
     * @return string
     */
    private function getRefreshTokenForm(): string
    {
        $form = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->authentication->refresh_token,
        ];

        return http_build_query($form);
    }

    /**
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $headers
     * @param  array  $body
     * @return \Seat\Eseye\Containers\EsiResponse
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     */
    public function httpRequest(string $method, string $uri, array $headers = [], array $body = []): EsiResponse
    {

        // Include some basic headers to those already passed in. Everything
        // is considered to be json.
        $headers = array_merge($headers, [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent'   => 'Eseye/' . Eseye::VERSION . '/' . Configuration::getInstance()->http_user_agent,
        ]);

        // Add some debug logging and start measuring how long the request took.
        $this->logger->debug('Making ' . $method . ' request to ' . $uri);
        $start = microtime(true);

        $request = $this->request_factory->createRequest($method, $uri);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if (count($body) > 0) {
            $stream = $this->stream_factory->createStream(json_encode($body));
            $request = $request->withBody($stream);
        }

        // Make the _actual_ request to ESI
        $response = $this->getClient()->sendRequest($request);

        $log_level = LogLevel::INFO;

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 600)
            $log_level = LogLevel::ERROR;

        // Log the request.
        $this->logger->log($log_level, '[http ' . $response->getStatusCode() . ', ' .
            strtolower($response->getReasonPhrase()) . '] ' .
            $method . ' -> ' . $this->stripRefreshTokenValue($uri) . ' [t/e: ' .
            number_format(microtime(true) - $start, 2) . 's/' .
            implode(' ', $response->getHeader('X-Esi-Error-Limit-Remain')) . ']'
        );

        // Grab the body from the StreamInterface instance.
        $content = $response->getBody()->getContents();

        // For debugging purposes, log the response body
        $this->logger->debug('[http ' . $response->getStatusCode() . ', ' . strtolower($response->getReasonPhrase()) . '] ' . $method . ' -> ' . $this->stripRefreshTokenValue($uri), [
            'body' => $content,
        ]);

        if ($log_level == LogLevel::ERROR) {

            // Raise the exception that should be handled by the caller
            throw new RequestFailedException($this->makeEsiResponse(
                $content,
                $response->getHeaders(),
                'now',
                $response->getStatusCode())
            );
        }

        // Return a container response that can be parsed.
        return $this->makeEsiResponse(
            $content,
            $response->getHeaders(),
            $response->hasHeader('Expires') ? $response->getHeader('Expires')[0] : 'now',
            $response->getStatusCode()
        );
    }

    /**
     * @return \Psr\Http\Client\ClientInterface
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function getClient(): ClientInterface
    {
        if (! $this->client)
            $this->client = Configuration::getInstance()->getHttpClient();

        return $this->client;
    }

    /**
     * @param  \Psr\Http\Client\ClientInterface  $client
     */
    public function setClient(ClientInterface $client): void
    {
        $this->client = $client;
    }

    /**
     * @param  string  $uri
     * @return string
     */
    public function stripRefreshTokenValue(string $uri): string
    {
        // If we have 'refresh_token' in the URI, strip it.
        if (strpos($uri, 'refresh_token'))
            return Uri::withoutQueryValue(new Uri($uri), 'refresh_token')
                ->__toString();

        return $uri;
    }

    /**
     * @param  string  $body
     * @param  array  $headers
     * @param  string  $expires
     * @param  int  $status_code
     * @return \Seat\Eseye\Containers\EsiResponse
     */
    public function makeEsiResponse(string $body, array $headers, string $expires, int $status_code): EsiResponse
    {
        return new EsiResponse($body, $headers, $expires, $status_code);
    }

    /**
     * @return array
     */
    public function getAuthenticationScopes(): array
    {
        // If we don't have any authentication data, then
        // only public calls can be made.
        if (is_null($this->getAuthentication()))
            return ['public'];

        try {
            // If there are no scopes that we know of, update them.
            // There will always be at least 1 as we add the internal
            // 'public' scope.
            if (count($this->getAuthentication()->scopes) <= 0)
                $this->setAuthenticationScopes();
        } catch (InvalidClaimException $e) {
            if ($e->getClaim() !== 'exp')
                throw $e;

            $this->refreshToken();
        }

        return $this->getAuthentication()->scopes;
    }

    /**
     * Query the eveseat/resources repository for SDE
     * related information.
     *
     * @return void
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Seat\Eseye\Exceptions\DiscoverServiceNotAvailableException
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function setAuthenticationScopes(): void
    {
        $jws_token = $this->jwt_validator->validateToken($this->authentication->client_id, $this->authentication->access_token);

        $this->authentication->scopes = $jws_token['scp'];
    }
}
