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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Jose\Component\Core\JWKSet;
use Jose\Easy\Load;
use Seat\Eseye\Checker\Claim\AzpChecker;
use Seat\Eseye\Checker\Claim\NameChecker;
use Seat\Eseye\Checker\Claim\OwnerChecker;
use Seat\Eseye\Checker\Claim\SubEveCharacterChecker;
use Seat\Eseye\Checker\Header\TypeChecker;
use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\Eseye;
use Seat\Eseye\Exceptions\InvalidAuthenticationException;
use Seat\Eseye\Exceptions\RequestFailedException;

/**
 * Class GuzzleFetcher.
 *
 * @package Seat\Eseye\Fetchers
 */
class GuzzleFetcher implements FetcherInterface
{

    /**
     * @var string
     */
    protected $authentication;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var \Seat\Eseye\Log\LogInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $sso_base;

    /**
     * EseyeFetcher constructor.
     *
     * @param  \Seat\Eseye\Containers\EsiAuthentication  $authentication
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function __construct(EsiAuthentication $authentication = null)
    {

        $this->authentication = $authentication;

        // Setup the logger
        $this->logger = Configuration::getInstance()->getLogger();
        $this->sso_base = sprintf('%s://%s:%d/v2/oauth',
            Configuration::getInstance()->sso_scheme,
            Configuration::getInstance()->sso_host,
            Configuration::getInstance()->sso_port);
    }

    /**
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $body
     * @param  array  $headers
     * @return \Seat\Eseye\Containers\EsiResponse
     *
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function call(
        string $method, string $uri, array $body, array $headers = []): EsiResponse
    {

        // If we have authentication data, add the
        // Authorization header.
        if ($this->getAuthentication())
            $headers = array_merge($headers, [
                'Authorization' => 'Bearer ' . $this->getToken(),
            ]);

        return $this->httpRequest($method, $uri, $headers, $body);
    }

    /**
     * @return \Seat\Eseye\Containers\EsiAuthentication|null
     */
    public function getAuthentication()
    {

        return $this->authentication;
    }

    /**
     * @param  \Seat\Eseye\Containers\EsiAuthentication  $authentication
     *
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     */
    public function setAuthentication(EsiAuthentication $authentication)
    {

        if (! $authentication->valid())
            throw new InvalidAuthenticationException('Authentication data invalid/empty');

        $this->authentication = $authentication;
    }

    /**
     * @return string
     *
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
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
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    private function refreshToken()
    {

        // Make the post request for a new access_token
        try {

            $response = $this->getClient()->post($this->sso_base . '/token',
                [
                    'form_params' => [
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $this->authentication->refresh_token,
                    ],
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode(
                            $this->authentication->client_id . ':' . $this->authentication->secret),
                        'User-Agent'   => 'Eseye/' . Eseye::VERSION . '/' .
                            Configuration::getInstance()->http_user_agent,
                    ],
                ]
            );

        } catch (ClientException|ServerException $e) {

            // Log the event as failed
            $this->logger->error('[http ' . $e->getResponse()->getStatusCode() . ', ' .
                strtolower($e->getResponse()->getReasonPhrase()) . '] ' .
                'get -> ' . $this->sso_base . '/token'
            );

            // Grab the body from the StreamInterface intance.
            $responseBody = $e->getResponse()->getBody()->getContents();

            // For debugging purposes, log the response body
            $this->logger->debug('Request for get -> ' . $this->sso_base . '/token failed. Response body was: ' .
                $responseBody);

            // Raise the exception that should be handled by the caller
            throw new RequestFailedException($e, $this->makeEsiResponse(
                $responseBody,
                $e->getResponse()->getHeaders(),
                'now',
                $e->getResponse()->getStatusCode())
            );
        }

        $response = json_decode($response->getBody()->getContents());

        // Get the current EsiAuth container
        $authentication = $this->getAuthentication();

        $jws_token = $this->verifyToken($response->access_token);

        $this->logger->debug(json_encode($jws_token));

        // Set the new authentication values from the request
        $authentication->access_token = $response->access_token;
        $authentication->refresh_token = $response->refresh_token;
        $authentication->token_expires = $jws_token['exp'];

        // ... and update the container
        $this->setAuthentication($authentication);
    }

    /**
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $headers
     * @param  array  $body
     * @return mixed|\Seat\Eseye\Containers\EsiResponse
     *
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function httpRequest(
        string $method, string $uri, array $headers = [], array $body = []): EsiResponse
    {

        // Include some basic headers to those already passed in. Everything
        // is considered to be json.
        $headers = array_merge($headers, [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        // Add some debug logging and start measuring how long the request took.
        $this->logger->debug('Making ' . $method . ' request to ' . $uri);
        $start = microtime(true);

        // Json encode the body if it has data, else just null it
        if (count($body) > 0)
            $body = json_encode($body);
        else
            $body = null;

        try {

            // Make the _actual_ request to ESI
            $response = $this->getClient()->send(
                new Request($method, $uri, $headers, $body));

        } catch (ClientException|ServerException $e) {

            // Log the event as failed
            $this->logger->error('[http ' . $e->getResponse()->getStatusCode() . ', ' .
                strtolower($e->getResponse()->getReasonPhrase()) . '] ' .
                $method . ' -> ' . $this->stripRefreshTokenValue($uri) . ' [t/e: ' .
                number_format(microtime(true) - $start, 2) . 's/' .
                implode(' ', $e->getResponse()->getHeader('X-Esi-Error-Limit-Remain')) . ']'
            );

            // Grab the body from the StreamInterface intance.
            $responseBody = $e->getResponse()->getBody()->getContents();

            // For debugging purposes, log the response body
            $this->logger->debug('Request for ' . $method . ' -> ' . $uri . ' failed. Response body was: ' .
                $responseBody);

            // Raise the exception that should be handled by the caller
            throw new RequestFailedException($e, $this->makeEsiResponse(
                $responseBody,
                $e->getResponse()->getHeaders(),
                'now',
                $e->getResponse()->getStatusCode())
            );
        }

        // Log the successful request.
        $this->logger->log('[http ' . $response->getStatusCode() . ', ' .
            strtolower($response->getReasonPhrase()) . '] ' .
            $method . ' -> ' . $this->stripRefreshTokenValue($uri) . ' [t/e: ' .
            number_format(microtime(true) - $start, 2) . 's/' .
            implode(' ', $response->getHeader('X-Esi-Error-Limit-Remain')) . ']'
        );

        // Return a container response that can be parsed.
        return $this->makeEsiResponse(
            $response->getBody()->getContents(),
            $response->getHeaders(),
            $response->hasHeader('Expires') ? $response->getHeader('Expires')[0] : 'now',
            $response->getStatusCode()
        );
    }

    /**
     * @return \GuzzleHttp\Client
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function getClient(): Client
    {

        if (! $this->client)
            $this->client = new Client([
                'timeout' => 30,
                'headers' => [
                    'User-Agent'   => 'Eseye/' . Eseye::VERSION . '/' .
                        Configuration::getInstance()->http_user_agent,
                ],
            ]);

        return $this->client;
    }

    /**
     * @param  \GuzzleHttp\Client  $client
     */
    public function setClient(Client $client)
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
    public function makeEsiResponse(
        string $body, array $headers, string $expires, int $status_code): EsiResponse
    {

        return new EsiResponse($body, $headers, $expires, $status_code);
    }

    /**
     * @return array
     *
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     */
    public function getAuthenticationScopes(): array
    {

        // If we don't have any authentication data, then
        // only public calls can be made.
        if (is_null($this->getAuthentication()))
            return ['public'];

        // If there are no scopes that we know of, update them.
        // There will always be at least 1 as we add the internal
        // 'public' scope.
        if (count($this->getAuthentication()->scopes) <= 0)
            $this->setAuthenticationScopes();

        return $this->getAuthentication()->scopes;
    }

    /**
     * Query the eveseat/resources repository for SDE
     * related information.
     *
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     */
    public function setAuthenticationScopes()
    {

        $jws_token = $this->verifyToken($this->authentication->access_token);

        $this->authentication->scopes = $jws_token['scp'];
    }

    /**
     * Verify that an access_token is still valid.
     *
     * @param  string  $access_token
     * @return array
     *
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Exception
     */
    private function verifyToken(string $access_token)
    {

        $sets = $this->getJwkSets();

        $jwk_sets = JWKSet::createFromKeyData($sets);

        $jws = Load::jws($access_token)
            ->algs(['RS256', 'ES256', 'HS256'])
            ->exp()
            ->iss(Configuration::getInstance()->sso_iss)
            ->header('typ', new TypeChecker(['JWT'], true))
            ->claim('sub', new SubEveCharacterChecker())
            ->claim('azp', new AzpChecker($this->authentication->client_id))
            ->claim('name', new NameChecker())
            ->claim('owner', new OwnerChecker())
            ->keyset($jwk_sets)
            ->run();

        return $jws->claims->all();
    }

    /**
     * @return array
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    private function getJwkSets(): array
    {
        $jwk_uri = $this->getJwkUri();

        $response = $this->getClient()->get($jwk_uri);

        return json_decode($response->getBody(), true);
    }

    /**
     * @return string
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    private function getJwkUri(): string
    {
        $oauth_discovery = sprintf('%s://%s:%d/.well-known/oauth-authorization-server',
            Configuration::getInstance()->sso_scheme,
            Configuration::getInstance()->sso_host,
            Configuration::getInstance()->sso_port);

        $response = $this->getClient()->get($oauth_discovery);

        $metadata = json_decode($response->getBody());

        return $metadata->jwks_uri;
    }
}
