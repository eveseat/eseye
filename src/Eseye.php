<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015, 2016, 2017, 2018  Leon Jacobs
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

use GuzzleHttp\Psr7\Uri;
use Seat\Eseye\Access\AccessInterface;
use Seat\Eseye\Access\CheckAccess;
use Seat\Eseye\Cache\CacheInterface;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\Exceptions\EsiScopeAccessDeniedException;
use Seat\Eseye\Exceptions\InvalidAuthenticationException;
use Seat\Eseye\Exceptions\InvalidContainerDataException;
use Seat\Eseye\Exceptions\UriDataMissingException;
use Seat\Eseye\Fetchers\FetcherInterface;
use Seat\Eseye\Log\LogInterface;

/**
 * Class Eseye.
 * @package Seat\Eseye
 */
class Eseye
{

    /**
     * The Eseye Version.
     */
    const VERSION = '1.1.6';

    /**
     * @var \Seat\Eseye\Containers\EsiAuthentication
     */
    protected $authentication;

    /**
     * @var
     */
    protected $fetcher;

    /**
     * @var
     */
    protected $cache;

    /**
     * @var \Seat\Eseye\Log\LogInterface
     */
    protected $logger;

    /**
     * @var
     */
    protected $access_checker;

    /**
     * @var array
     */
    protected $query_string = [];

    /**
     * @var array
     */
    protected $request_body = [];

    /**
     * @var string
     */
    protected $esi = [
        'scheme' => 'https',
        'host'   => 'esi.evetech.net',
    ];

    /**
     * @var string
     */
    protected $version = '/latest';

    /**
     * HTTP verbs that could have their responses cached.
     *
     * @var array
     */
    protected $cachable_verb = ['get'];

    /**
     * Eseye constructor.
     *
     * @param \Seat\Eseye\Containers\EsiAuthentication $authentication
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function __construct(
        EsiAuthentication $authentication = null)
    {

        if (! is_null($authentication))
            $this->authentication = $authentication;

        // Setup the logger
        $this->logger = $this->getLogger();

        return $this;
    }

    /**
     * @return \Seat\Eseye\Log\LogInterface
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function getLogger(): LogInterface
    {

        return $this->getConfiguration()->getLogger();
    }

    /**
     * @return \Seat\Eseye\Configuration
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function getConfiguration(): Configuration
    {

        return Configuration::getInstance();
    }

    /**
     * @return \Seat\Eseye\Containers\EsiAuthentication
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     */
    public function getAuthentication(): EsiAuthentication
    {

        if (is_null($this->authentication))
            throw new InvalidAuthenticationException('Authentication data not set.');

        return $this->authentication;
    }

    /**
     * @param \Seat\Eseye\Containers\EsiAuthentication $authentication
     *
     * @return \Seat\Eseye\Eseye
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function setAuthentication(EsiAuthentication $authentication): self
    {

        if (! $authentication->valid())
            throw new InvalidContainerDataException('Authentication data invalid/empty');

        $this->authentication = $authentication;

        return $this;
    }

    /**
     * @param string $refreshToken
     *
     * @return \Seat\Eseye\Eseye
     */
    public function setRefreshToken(String $refreshToken): self
    {

        $this->authentication = $this->authentication->setRefreshToken($refreshToken);

        return $this;
    }

    /**
     * @param \Seat\Eseye\Fetchers\FetcherInterface $fetcher
     */
    public function setFetcher(FetcherInterface $fetcher)
    {

        $this->fetcher = $fetcher;
    }

    /**
     * @param array $body
     *
     * @return \Seat\Eseye\Eseye
     */
    public function setBody(array $body): self
    {

        $this->request_body = $body;

        return $this;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $uri_data
     *
     * @return \Seat\Eseye\Containers\EsiResponse
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function invoke(string $method, string $uri, array $uri_data = []): EsiResponse
    {

        // Check the Access Requirement
        if (! $this->getAccessChecker()->can(
            $method, $uri, $this->getFetcher()->getAuthenticationScopes())
        ) {

            // Build the uri so that there is context around what is denied.
            $uri = $this->buildDataUri($uri, $uri_data);

            // Log the deny.
            $this->logger->warning('Access denied to ' . $uri . ' due to ' .
                'missing scopes.');

            throw new EsiScopeAccessDeniedException('Access denied to ' . $uri);
        }

        // Build the URI from the parts we have.
        $uri = $this->buildDataUri($uri, $uri_data);

        // Check if there is a cached response we can return
        if (in_array(strtolower($method), $this->cachable_verb) &&
            $cached = $this->getCache()->get($uri->getPath(), $uri->getQuery())
        ) {

            // Mark the response as one that was loaded from the cache
            $cached->setIsCachedload();

            // Perform some debug logging
            $this->getLogger()->debug('Loaded cached response for ' . $method . ' -> ' . $uri);

            return $cached;
        }

        // Call ESI itself and get the EsiResponse
        $result = $this->rawFetch($method, $uri, $this->getBody());

        // Cache the response if it was a get and is not already expired
        if (in_array(strtolower($method), $this->cachable_verb) && ! $result->expired())
            $this->getCache()->set($uri->getPath(), $uri->getQuery(), $result);

        // In preparation for the next request, perform some
        // self cleanups of this objects request data such as
        // query string parameters and post bodies.
        $this->cleanupRequestData();

        return $result;
    }

    /**
     * @return \Seat\Eseye\Access\CheckAccess
     */
    public function getAccessChecker()
    {

        if (! $this->access_checker)
            $this->access_checker = new CheckAccess;

        return $this->access_checker;
    }

    /**
     * @param \Seat\Eseye\Access\AccessInterface $checker
     *
     * @return \Seat\Eseye\Eseye
     */
    public function setAccessChecker(AccessInterface $checker): self
    {

        $this->access_checker = $checker;

        return $this;
    }

    /**
     * @return \Seat\Eseye\Fetchers\FetcherInterface
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    private function getFetcher(): FetcherInterface
    {

        if (! $this->fetcher) {

            $fetcher_class = $this->getConfiguration()->fetcher;
            $this->fetcher = new $fetcher_class(...[$this->authentication]);

        }

        return $this->fetcher;
    }

    /**
     * @param string $uri
     * @param array  $data
     *
     * @return \GuzzleHttp\Psr7\Uri
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function buildDataUri(string $uri, array $data): Uri
    {

        // Create a query string for the URI. We automatically
        // include the datasource value from the configuration.
        $query_params = array_merge([
            'datasource' => $this->getConfiguration()->datasource,
        ], $this->getQueryString());

        return Uri::fromParts([
            'scheme' => $this->esi['scheme'],
            'host'   => $this->esi['host'],
            'path'   => rtrim($this->getVersion(), '/') .
                $this->mapDataToUri($uri, $data),
            'query'  => http_build_query($query_params),
        ]);
    }

    /**
     * @return array
     */
    public function getQueryString(): array
    {

        return $this->query_string;
    }

    /**
     * @param array $query
     *
     * @return \Seat\Eseye\Eseye
     */
    public function setQueryString(array $query): self
    {

        foreach ($query as $key => $value) {
            if (is_array($value)) {
                $query[$key] = implode(',', $value);
            }
        }

        $this->query_string = array_merge($this->query_string, $query);

        return $this;
    }

    /**
     * Get the versioned baseURI to use.
     *
     * @return string
     */
    public function getVersion(): string
    {

        return $this->version;
    }

    /**
     * Set the version of the API endpoints base URI.
     *
     * @param string $version
     *
     * @return \Seat\Eseye\Eseye
     */
    public function setVersion(string $version)
    {

        if (substr($version, 0, 1) !== '/')
            $version = '/' . $version;

        $this->version = $version;

        return $this;
    }

    /**
     * @param string $uri
     * @param array  $data
     *
     * @return string
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    private function mapDataToUri(string $uri, array $data): string
    {

        // Extract fields in curly braces. If there are fields,
        // replace the data with those in the URI
        if (preg_match_all('/{+(.*?)}/', $uri, $matches)) {

            if (empty($data))
                throw new UriDataMissingException(
                    'The data array for the uri ' . $uri . ' is empty. Please provide data to use.');

            foreach ($matches[1] as $match) {

                if (! array_key_exists($match, $data))
                    throw new UriDataMissingException(
                        'Data for ' . $match . ' is missing. Please provide this by setting a value ' .
                        'for ' . $match . '.');

                $uri = str_replace('{' . $match . '}', $data[$match], $uri);
            }
        }

        return $uri;
    }

    /**
     * @return \Seat\Eseye\Cache\CacheInterface
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    private function getCache(): CacheInterface
    {

        return $this->getConfiguration()->getCache();
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $body
     *
     * @return mixed
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function rawFetch(string $method, string $uri, array $body)
    {

        return $this->getFetcher()->call($method, $uri, $body);
    }

    /**
     * @return array
     */
    public function getBody(): array
    {

        return $this->request_body;
    }

    /**
     * @return \Seat\Eseye\Eseye
     */
    public function cleanupRequestData(): self
    {

        $this->unsetBody();
        $this->unsetQueryString();

        return $this;
    }

    /**
     * @return \Seat\Eseye\Eseye
     */
    public function unsetBody(): self
    {

        $this->request_body = [];

        return $this;
    }

    /**
     * @return \Seat\Eseye\Eseye
     */
    public function unsetQueryString(): self
    {

        $this->query_string = [];

        return $this;
    }

    /**
     * A helper method to specify the page to retrieve.
     *
     * @param int $page
     *
     * @return \Seat\Eseye\Eseye
     */
    public function page(int $page): self
    {

        $this->setQueryString(['page' => $page]);

        return $this;
    }
}
