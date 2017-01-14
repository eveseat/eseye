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
use GuzzleHttp\Psr7\Request;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Containers\EsiResponse;

/**
 * Class EseyeFetcher
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
    }

    /**
     * @param string $method
     * @param string $uri
     *
     * @return mixed
     */
    public function call(string $method, string $uri): EsiResponse
    {

        return $this->httpRequest($method, $uri, [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $this->getToken(),
        ]);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $headers
     *
     * @return mixed
     */
    public function httpRequest(
        string $method, string $uri, array $headers = []): EsiResponse
    {

        $response = $this->client->send(
            new Request($method, $uri, $headers));

        return new EsiResponse(
            json_decode($response->getBody()),
            $response->hasHeader('Expires') ?
                $response->getHeader('Expires')[0] : 'now',
            $response->getStatusCode()
        );
    }

    /**
     * @return \Seat\Eseye\Containers\EsiAuthentication
     */
    public function getAuthentication(): EsiAuthentication
    {

        return $this->authentication;
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
     * @return array
     */
    public function getAuthenticationScopes(): array
    {

        if (empty($this->getAuthentication()->scopes))
            $this->setAuthenticationScopes();

        return $this->getAuthentication()->scopes;
    }

    /**
     * Verify a token and set the Authentication scopes
     */
    public function setAuthenticationScopes()
    {

        $scopes = $this->verifyToken()['Scopes'];
        $this->authentication->scopes = explode(' ', $scopes);
    }

    /**
     * Verify that an access_token is still valid.
     */
    private function verifyToken()
    {

        return $this->httpRequest('get', $this->sso_base . '/verify/', [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $this->getToken(),
        ]);
    }

    /**
     * Refresh the Access token that we have in the EsiAccess container
     */
    private function refreshToken()
    {

        // Make the post request for a new access_token
        $response = $this->httpRequest('post',
            $this->sso_base . '/token/?grant_type=refresh_token&refresh_token=' .
            $this->authentication->refresh_token, [
                'Accept'        => 'application/json',
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
