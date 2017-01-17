<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015, 2016, 2017  Leon Jacobs
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

namespace Seat\Eseye;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\Exceptions\RequestFailedException;
use stdClass;

/**
 * Class EseyeFetcher.
 * @package Seat\Eseye
 */
class EseyeFetcher
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
    protected $sso_base = 'https://login.eveonline.com/oauth';

    /**
     * EseyeFetcher constructor.
     *
     * @param \Seat\Eseye\Containers\EsiAuthentication $authentication
     */
    public function __construct(EsiAuthentication $authentication)
    {

        $this->authentication = $authentication;
        $this->client = new Client();

        // Setup the logger
        $this->logger = Configuration::getInstance()->getLogger();
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $body
     *
     * @return mixed|\Seat\Eseye\Containers\EsiResponse
     */
    public function call(string $method, string $uri, array $body): EsiResponse
    {

        return $this->httpRequest($method, $uri, [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->getToken(),
        ], $body);
    }

    /**
     * @param string $uri
     *
     * @return string
     */
    private function stripRefreshTokenValue(string $uri): string
    {

        // If we have 'refresh_token' in the URI, strip it.
        if (strpos($uri, 'refresh_token'))
            return Uri::withoutQueryValue((new Uri($uri)), 'refresh_token')
                ->__toString();

        return $uri;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $headers
     * @param array  $body
     *
     * @return mixed|\Seat\Eseye\Containers\EsiResponse
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     */
    public function httpRequest(
        string $method, string $uri, array $headers = [], array $body = []): EsiResponse
    {

        // Add some debug logging and start measuring how long the request took.
        $this->logger->debug('Making ' . $method . ' request to ' . $uri);
        $start = microtime(true);

        try {

            // Make the _actual_ request to ESI
            $response = $this->client->send(
                new Request($method, $uri, $headers, json_encode($body)));

        } catch (ClientException $e) {

            // Log the event as failed
            $this->logger->error('[http ' . $e->getResponse()->getStatusCode() . '] ' .
                '[' . $e->getResponse()->getReasonPhrase() . '] ' .
                $method . ' -> ' . $this->stripRefreshTokenValue($uri) . ' [' .
                number_format(microtime(true) - $start, 2) . 's]');

            // Raise the exception that should be handled by the calling
            throw new RequestFailedException($e,
                $this->makeEsiResponse(
                    (object) json_decode($e->getResponse()->getBody()), 'now',
                    $e->getResponse()->getStatusCode())
            );

        }

        // Log the event.
        $this->logger->log('[http ' . $response->getStatusCode() . '] ' .
            $method . ' -> ' . $this->stripRefreshTokenValue($uri) . ' [' .
            number_format(microtime(true) - $start, 2) . 's]');

        // Return a container response that can be parsed.
        return $this->makeEsiResponse(
            (object) json_decode($response->getBody()),
            $response->hasHeader('Expires') ? $response->getHeader('Expires')[0] : 'now',
            $response->getStatusCode()
        );
    }

    /**
     * @param \stdClass $body
     * @param string    $expires
     * @param int       $status_code
     *
     * @return \Seat\Eseye\Containers\EsiResponse
     */
    public function makeEsiResponse(
        stdClass $body, string $expires, int $status_code): EsiResponse
    {

        return new EsiResponse($body, $expires, $status_code);
    }

    /**
     * @return \Seat\Eseye\Containers\EsiAuthentication
     */
    public function getAuthentication(): EsiAuthentication
    {

        return $this->authentication;
    }

    /**
     * @return array
     */
    public function getAuthenticationScopes(): array
    {

        // If there are no scopes that we know of, update them.
        // There will always be at least 1 as we add the internal
        // 'public' scope.
        if (count($this->getAuthentication()->scopes) <= 0)
            $this->setAuthenticationScopes();

        return $this->getAuthentication()->scopes;
    }

    /**
     * Verify a token and set the Authentication scopes.
     */
    public function setAuthenticationScopes()
    {

        $scopes = $this->verifyToken()['Scopes'];

        // Add the internal 'public' scope
        $scopes = $scopes . ' public';
        $this->authentication->scopes = explode(' ', $scopes);
    }

    /**
     * Verify that an access_token is still valid.
     */
    private function verifyToken()
    {

        return $this->httpRequest('get', $this->sso_base . '/verify/', [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->getToken(),
        ]);
    }

    /**
     * @return string
     */
    private function getToken(): string
    {

        // Check the expiry date.
        $expires = carbon($this->getAuthentication()->token_expires);

        // If the token expires in the next 5 minues, refresh it.
        if ($expires <= carbon('now')->addMinute(5))
            $this->refreshToken();

        return $this->getAuthentication()->access_token;
    }

    /**
     * Refresh the Access token that we have in the EsiAccess container.
     */
    private function refreshToken()
    {

        // Make the post request for a new access_token
        $response = $this->httpRequest('post',
            $this->sso_base . '/token/?grant_type=refresh_token&refresh_token=' .
            $this->authentication->refresh_token, [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic ' .
                    base64_encode($this->authentication->client_id . ':' .
                        $this->authentication->secret),
            ]
        );

        // Get the current EsiAuth container
        $authentication = $this->getAuthentication();

        // Set the new authentication values from the request
        $authentication->access_token = $response->access_token;
        $authentication->refresh_token = $response->refresh_token;
        $authentication->token_expires = carbon('now')
            ->addSeconds($response->expires_in);

        // ... and update the container
        $this->authentication = $authentication;
    }
}
