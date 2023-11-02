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

namespace Seat\Eseye\Checker;

use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Checker\AudienceChecker;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\IssuerChecker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSLoader;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Seat\Eseye\Checker\Claim\AzpChecker;
use Seat\Eseye\Checker\Claim\NameChecker;
use Seat\Eseye\Checker\Claim\OwnerChecker;
use Seat\Eseye\Checker\Claim\ScpChecker;
use Seat\Eseye\Checker\Claim\SubEveCharacterChecker;
use Seat\Eseye\Configuration;
use Seat\Eseye\Exceptions\DiscoverServiceNotAvailableException;
use Seat\Eseye\Exceptions\InvalidAuthenticationException;

class EsiTokenValidator
{
    private ClientInterface $client;
    private JWSLoader $loader;
    private RequestFactoryInterface $request_factory;

    public function __construct()
    {
        $header_checker_manager = $this->getJWTHeadersPolicy();
        $serialize_manager = $this->getSerializerManager();
        $jws_verifier = $this->getJWTVerifier();

        // Init the HTTP client
        $this->client = Configuration::getInstance()->getHttpClient();
        $this->request_factory = Configuration::getInstance()->getHttpRequestFactory();

        $this->loader = new JWSLoader($serialize_manager, $jws_verifier, $header_checker_manager);
    }

    /**
     * Validate provided access token and return its claims.
     *
     * @param  string  $access_token
     * @return array
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Seat\Eseye\Exceptions\DiscoverServiceNotAvailableException
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function validateToken(string $client_id, string $access_token): array
    {
        $sets = $this->getJwkSets();

        $jwk_sets = JWKSet::createFromKeyData($sets);

        $claims_checker_manager = $this->getJWTClaimsPolicy($client_id);

        // convert raw access token into a JWS Token object
        $jws = $this->loader->getSerializerManager()->unserialize($access_token);

        // apply token headers policy
        $this->loader->getHeaderCheckerManager()->check($jws, 0, ['alg']);

        // validate token signature
        if (! $this->loader->getJwsVerifier()->verifyWithKeySet($jws, $jwk_sets, 0))
            throw new InvalidAuthenticationException('Unable to verify access token.');

        // apply claims policy
        $claims = json_decode($jws->getPayload(), true);

        return $claims_checker_manager->check($claims, ['iss', 'exp', 'aud']);
    }

    /**
     * @return \Jose\Component\Signature\Serializer\JWSSerializerManager
     */
    private function getSerializerManager(): JWSSerializerManager
    {
        return new JWSSerializerManager([
            new CompactSerializer(),
        ]);
    }

    /**
     * @return \Jose\Component\Signature\JWSVerifier
     */
    private function getJWTVerifier(): JWSVerifier
    {
        $algorithms_manager = new AlgorithmManager([
            new RS256(),
        ]);

        return new JWSVerifier($algorithms_manager);
    }

    /**
     * @return \Jose\Component\Checker\HeaderCheckerManager
     */
    private function getJWTHeadersPolicy(): HeaderCheckerManager
    {
        return new HeaderCheckerManager(
            [
                new AlgorithmChecker(['RS256']),
            ],
            [
                new JWSTokenSupport(),
            ]
        );
    }

    /**
     * @param  string  $client_id
     * @return \Jose\Component\Checker\ClaimCheckerManager
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    private function getJWTClaimsPolicy(string $client_id): ClaimCheckerManager
    {
        return new ClaimCheckerManager([
            new IssuerChecker([
                sprintf('%s://%s', Configuration::getInstance()->sso_scheme, Configuration::getInstance()->sso_host),
            ]),
            new ExpirationTimeChecker(),
            new AudienceChecker('EVE Online'),
            new SubEveCharacterChecker(),
            new ScpChecker(),
            new AzpChecker($client_id),
            new NameChecker(),
            new OwnerChecker(),
        ]);
    }

    /**
     * @return array
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\DiscoverServiceNotAvailableException
     */
    private function getJwkSets(): array
    {
        $metadata = $this->getAuthServerMetadata();
        $jwk_set_uri = $metadata->jwks_uri;

        $request = $this->request_factory->createRequest('GET', $jwk_set_uri);
        $response = $this->client->sendRequest($request);

        return json_decode($response->getBody(), true);
    }

    /**
     * @return object
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\DiscoverServiceNotAvailableException
     */
    private function getAuthServerMetadata(): object
    {
        $oauth_discovery = sprintf('%s://%s:%d/.well-known/oauth-authorization-server',
            Configuration::getInstance()->sso_scheme,
            Configuration::getInstance()->sso_host,
            Configuration::getInstance()->sso_port);

        $request = $this->request_factory->createRequest('GET', $oauth_discovery);
        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() >= 400)
            throw new DiscoverServiceNotAvailableException($response->getBody()->getContents());

        return json_decode($response->getBody());
    }
}
