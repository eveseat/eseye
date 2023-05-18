<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015 to 2022 Leon Jacobs
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
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Seat\Eseye\Access\AccessInterface;
use Seat\Eseye\Access\CheckAccess;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\Exceptions\EsiScopeAccessDeniedException;
use Seat\Eseye\Exceptions\InvalidAuthenticationException;
use Seat\Eseye\Exceptions\InvalidContainerDataException;
use Seat\Eseye\Exceptions\UriDataMissingException;
use Seat\Eseye\Fetchers\FetcherInterface;

/**
 * Class Eseye.
 *
 * @package Seat\Eseye
 */
class Eseye
{

    /**
     * The Eseye Version.
     */
    const VERSION = '3.0.0';

    /**
     * @var \Seat\Eseye\Containers\EsiAuthentication|null
     */
    protected ?EsiAuthentication $authentication = null;

    /**
     * @var \Seat\Eseye\Fetchers\FetcherInterface|null
     */
    protected ?FetcherInterface $fetcher = null;

    /**
     * @var \Seat\Eseye\Access\AccessInterface|null
     */
    protected ?AccessInterface $access_checker = null;

    /**
     * @var array
     */
    protected array $query_string = [];

    /**
     * @var array
     */
    protected array $request_body = [];

    /**
     * @var string
     */
    protected string $version = '/latest';

    /**
     * HTTP verbs that could have their responses cached.
     *
     * @var array
     */
    protected array $cachable_verb = ['get'];

    /**
     * Eseye constructor.
     *
     * @param  \Seat\Eseye\Containers\EsiAuthentication|null  $authentication
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function __construct(EsiAuthentication $authentication = null)
    {
        if (! is_null($authentication))
            $this->authentication = $authentication;
    }

    /**
     * @return \Seat\Eseye\Configuration
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function getConfiguration(): Configuration
    {
        return Configuration::getInstance();
    }

    /**
     * @return \Psr\Log\LoggerInterface
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function getLogger(): LoggerInterface
    {
        return $this->getConfiguration()->getLogger();
    }

    /**
     * @return \Psr\SimpleCache\CacheInterface
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    private function getCache(): CacheInterface
    {
        return $this->getConfiguration()->getCache();
    }

    /**
     * @return \Seat\Eseye\Access\AccessInterface
     */
    public function getAccessChecker(): AccessInterface
    {
        if (! $this->access_checker)
            $this->access_checker = new CheckAccess;

        return $this->access_checker;
    }

    /**
     * @param  \Seat\Eseye\Access\AccessInterface  $checker
     * @return \Seat\Eseye\Eseye
     */
    public function setAccessChecker(AccessInterface $checker): self
    {
        $this->access_checker = $checker;

        return $this;
    }

    /**
     * @return \Seat\Eseye\Fetchers\FetcherInterface
     *
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
     * @param  \Seat\Eseye\Fetchers\FetcherInterface  $fetcher
     */
    public function setFetcher(FetcherInterface $fetcher): void
    {
        $this->fetcher = $fetcher;
    }

    /**
     * @return \Seat\Eseye\Containers\EsiAuthentication
     *
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     */
    public function getAuthentication(): EsiAuthentication
    {
        if (is_null($this->authentication))
            throw new InvalidAuthenticationException('Authentication data not set.');

        return $this->authentication;
    }

    /**
     * @param  \Seat\Eseye\Containers\EsiAuthentication  $authentication
     * @return \Seat\Eseye\Eseye
     *
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
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return ! is_null($this->authentication);
    }

    /**
     * @param  string  $refreshToken
     * @return \Seat\Eseye\Eseye
     */
    public function setRefreshToken(string $refreshToken): self
    {
        $this->authentication = $this->authentication->setRefreshToken($refreshToken);

        return $this;
    }

    /**
     * @return array
     */
    public function getBody(): array
    {

        return $this->request_body;
    }

    /**
     * @param  array  $body
     * @return \Seat\Eseye\Eseye
     */
    public function setBody(array $body): self
    {
        $this->request_body = $body;

        return $this;
    }

    /**
     * @return array
     */
    public function getQueryString(): array
    {
        return $this->query_string;
    }

    /**
     * @param  array  $query
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
     * @param  string  $version
     * @return \Seat\Eseye\Eseye
     */
    public function setVersion(string $version): Eseye
    {
        if (! str_starts_with($version, '/'))
            $version = '/' . $version;

        $this->version = $version;

        return $this;
    }

    /**
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $uri_data
     * @return \Seat\Eseye\Containers\EsiResponse
     *
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function invoke(string $method, string $endpoint, array $uri_data = []): EsiResponse
    {
        // Build the URI from the parts we have.
        $uri = $this->buildDataUri($endpoint, $uri_data);

        // Check the Access Requirement
        if (! $this->getAccessChecker()->can(
            $method, $endpoint, $this->getFetcher()->getAuthenticationScopes())
        ) {

            // Log the deny.
            $this->getConfiguration()->getLogger()->warning('Access denied to ' . $uri . ' due to missing scopes.');

            throw new EsiScopeAccessDeniedException('Access denied to ' . $uri);
        }

        // Attempt to retrieve request related response from cache handler
        $result = $this->handleCacheEntry($method, $uri);

        // Call ESI itself and get the EsiResponse in case it has not already been handled with cache control
        if (! isset($result))
            $result = $this->rawFetch($method, $uri, $this->getBody());

        // Cache the response if it was a get and is not already expired
        if ($this->isCachable($method, $result))
            $this->getCache()->set($uri, $result);

        // In preparation for the next request, perform some
        // self cleanups of this objects request data such as
        // query string parameters and post bodies.
        $this->cleanupRequestData();

        return $result;
    }

    /**
     * @param  string  $endpoint
     * @param  array  $data
     * @return \Psr\Http\Message\UriInterface
     *
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function buildDataUri(string $endpoint, array $data): UriInterface
    {
        // Create a query string for the URI. We automatically
        // include the datasource value from the configuration.
        $query_params = array_merge([
            'datasource' => $this->getConfiguration()->datasource,
        ], $this->getQueryString());

        return Uri::fromParts([
            'scheme' => $this->getConfiguration()->esi_scheme,
            'host'   => $this->getConfiguration()->esi_host,
            'port'   => $this->getConfiguration()->esi_port,
            'path'   => rtrim($this->getVersion(), '/') . $this->mapDataToUri($endpoint, $data),
            'query'  => http_build_query($query_params),
        ]);
    }

    /**
     * @param  string  $uri
     * @param  array  $data
     * @return string
     *
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
     * Determine if call can be cached.
     *
     * @param  string  $method
     * @param  EsiResponse  $response
     * @return bool
     */
    private function isCachable(string $method, EsiResponse $response): bool
    {
        $cache_control = array_map('strtolower', $response->getHeader('Cache-Control'));

        if (! in_array(strtolower($method), $this->cachable_verb))
            return false;

        if ($response->expired())
            return false;

        if (in_array('no-cache', $cache_control))
            return false;

        if (in_array('no-store', $cache_control))
            return false;

        return true;
    }

    /**
     * Handle request that might be cached.
     *
     * @param  string  $method
     * @param  \Psr\Http\Message\UriInterface  $uri
     * @return \Seat\Eseye\Containers\EsiResponse|null
     *
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     */
    private function handleCacheEntry(string $method, UriInterface $uri): ?EsiResponse
    {
        $cache_entry = $this->getCache()->get($uri);

        // If we were not able to retrieve data from cache, abort and return null
        if (! $cache_entry)
            return null;

        // In case the cached entry is still valid, mark content as being loaded from cache.
        if (! $cache_entry->expired())
            $cache_entry->setIsCachedLoad();

        // Handling ETag marked response specifically (ignoring the expired time)
        // Sending a request with the stored ETag in header - if we have a 304 response, data has not been altered.
        if ($cache_entry->hasHeader('ETag') && $cache_entry->expired()) {

            $result = $this->rawFetch($method, $uri, $this->getBody(), ['If-None-Match' => $cache_entry->getHeader('ETag')]);

            // in case response was distinct from 304 (unmodified) - return it directly
            if ($result->getErrorCode() !== 304)
                return $result;

            // update expires header with newly provided value
            $cache_entry->setExpires($result->expires());

            // store updated response in cache to renew internal cache duration
            $this->getCache()->set($uri, $cache_entry);

            $cache_entry->setIsCachedLoad();
        }

        // In case the result is effectively retrieved from cache,
        // return the cached element.
        if ($cache_entry->isCachedLoad()) {

            // Perform some debug logging
            $logging_msg = 'Loaded cached response for ' . $method . ' -> ' . $uri;

            if ($cache_entry->hasHeader('ETag'))
                $logging_msg = sprintf('%s [%s]', $logging_msg, $cache_entry->getHeader('ETag'));

            $this->getLogger()->debug($logging_msg);

            $this->cleanupRequestData();

            return $cache_entry;
        }

        return null;
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
    public function rawFetch(string $method, string $uri, array $body, array $headers = []): EsiResponse
    {

        return $this->getFetcher()->call($method, $uri, $body, $headers);
    }

    /**
     * @return \Seat\Eseye\Eseye
     */
    public function cleanupRequestData(): self
    {

        $this->setBody([]);
        $this->unsetQueryString();

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
     * @param  int  $page
     * @return \Seat\Eseye\Eseye
     */
    public function page(int $page): self
    {
        $this->setQueryString(['page' => $page]);

        return $this;
    }
}
