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

use GuzzleHttp\Psr7\Uri;
use Seat\Eseye\Access\AccessInterface;
use Seat\Eseye\Access\CheckAccess;
use Seat\Eseye\Cache\CacheInterface;
use Seat\Eseye\Cache\FileCache;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\Exceptions\InvalidAuthencationException;
use Seat\Eseye\Exceptions\InvalidContainerDataException;
use Seat\Eseye\Exceptions\UriDataMissingException;
use Seat\Eseye\Log\LogInterface;


/**
 * Class Eseye
 * @package Seat\Eseye
 */
class Eseye
{
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
     * @var
     */
    protected $access_checker;

    /**
     * @var string
     */
    protected $esi = [
        'scheme' => 'https',
        'host'   => 'esi.tech.ccp.is',
        'path'   => '/latest',
    ];

    /**
     * Eseye constructor.
     *
     * @param \Seat\Eseye\Containers\EsiAuthentication $authentication
     */
    public function __construct(
        EsiAuthentication $authentication = null)
    {

        if (! is_null($authentication))
            $this->authentication = $authentication;

        return $this;
    }

    /**
     * @param \Seat\Eseye\Containers\EsiAuthentication $authentication
     *
     * @return \Seat\Eseye\Eseye
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function setAuthentication(EsiAuthentication $authentication): Eseye
    {

        if (! $authentication->valid())
            throw new InvalidContainerDataException('Authentication data invalid/empty');

        $this->authentication = $authentication;

        return $this;
    }

    /**
     * @return \Seat\Eseye\Containers\EsiAuthentication
     * @throws \Seat\Eseye\Exceptions\InvalidAuthencationException
     */
    public function getAuthentication(): EsiAuthentication
    {

        if (is_null($this->authentication))
            throw new InvalidAuthencationException('Authentication data not set.');

        return $this->authentication;
    }

    /**
     * @return \Seat\Eseye\Configuration
     */
    public function getConfiguration(): Configuration
    {

        return Configuration::getInstance();
    }

    /**
     * @return \Seat\Eseye\Log\LogInterface
     */
    public function getLogger(): LogInterface
    {

        return $this->getConfiguration()->getLogger();
    }

    /**
     * @return \Seat\Eseye\EseyeFetcher
     */
    private function getFetcher(): EseyeFetcher
    {

        if (! $this->fetcher)
            $this->fetcher = new EseyeFetcher($this->authentication);

        return $this->fetcher;
    }

    /**
     * @param \Seat\Eseye\Cache\CacheInterface $cache
     */
    public function setCache(CacheInterface $cache)
    {

        $this->cache = $cache;
    }

    /**
     * @return \Seat\Eseye\Cache\FileCache
     */
    private function getCache()
    {

        if (! $this->cache)
            $this->cache = new FileCache;

        return $this->cache;
    }

    /**
     * @param \Seat\Eseye\Access\AccessInterface $checker
     */
    public function setAccessChecker(AccessInterface $checker)
    {

        $this->access_checker = $checker;
    }

    /**
     * @return \Seat\Eseye\Access\CheckAccess
     */
    public function getAccesChecker()
    {

        if (! $this->access_checker)
            $this->access_checker = new CheckAccess;

        return $this->access_checker;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $data
     *
     * @return mixed
     */
    public function invoke(
        string $method, string $uri, array $data = []): EsiResponse
    {

        // Check the Access Requirement
        if (! $this->getAccesChecker()->can(
            $method, $uri, $this->getFetcher()->getAuthenticationScopes())
        ) {

            echo 'ERROR: No access to call: ' . $uri . PHP_EOL;

            return;
        }

        // Build the URI from the template and data array
        $uri = Uri::fromParts([
            'scheme' => $this->esi['scheme'],
            'host'   => $this->esi['host'],
            'path'   => rtrim($this->esi['path'], '/') . $this->mapDataToUri($uri, $data),
            'query'  => 'datasource=' . $this->getConfiguration()->datasource,
        ]);

        // Call ESI itself
        return $this->rawFetch($method, $uri);
    }

    /**
     * @param string $method
     * @param string $uri
     *
     * @return mixed
     */
    public function rawFetch(string $method, string $uri)
    {

        return $this->getFetcher()->call($method, $uri);
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

}
