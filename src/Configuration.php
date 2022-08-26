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

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Seat\Eseye\Containers\EsiConfiguration;
use Seat\Eseye\Exceptions\InvalidConfigurationException;

/**
 * Class Configuration.
 *
 * @property string $sso_scheme
 * @property string $sso_host
 * @property int $sso_port
 * @property string $http_user_agent
 * @property string $datasource
 * @property string $esi_scheme
 * @property string $esi_host
 * @property int $esi_port
 * @property string $logger_level
 * @property string $logfile_location
 * @property int $log_max_files
 * @property string $file_cache_location
 * @property string $redis_cache_location
 * @property string $redis_cache_prefix
 * @property string $memcached_cache_host
 * @property string $memcached_cache_port
 * @property string $memcached_cache_prefix
 * @property int $memcached_cache_compressed
 * @property \Seat\Eseye\Fetchers\FetcherInterface $fetcher
 *
 * @package Seat\Eseye
 */
class Configuration
{
    /**
     * @var Configuration|null
     */
    private static ?Configuration $instance = null;

    /**
     * @var \Psr\Log\LoggerInterface|string|null
     */
    protected LoggerInterface|string|null $logger = null;

    /**
     * @var \Psr\SimpleCache\CacheInterface|string|null
     */
    protected CacheInterface|string|null $cache = null;

    /**
     * @var \Psr\Http\Client\ClientInterface|string|null
     */
    protected ClientInterface|string|null $http_client = null;

    /**
     * @var \Psr\Http\Message\StreamFactoryInterface|string|null
     */
    protected StreamFactoryInterface|string|null $http_stream_factory = null;

    /**
     * @var \Psr\Http\Message\RequestFactoryInterface|string|null
     */
    protected RequestFactoryInterface|string|null $http_request_factory = null;

    /**
     * @var EsiConfiguration
     */
    protected EsiConfiguration $configuration;

    /**
     * Configuration constructor.
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    private function __construct()
    {
        $this->configuration = new EsiConfiguration;
    }

    /**
     * @return \Seat\Eseye\Configuration
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public static function getInstance(): self
    {
        if (self::$instance === null)
            self::$instance = new self();

        return self::$instance;
    }

    /**
     * @return \Seat\Eseye\Containers\EsiConfiguration
     */
    public function getConfiguration(): EsiConfiguration
    {
        return $this->configuration;
    }

    /**
     * @param  \Seat\Eseye\Containers\EsiConfiguration  $configuration
     *
     * @throws \Seat\Eseye\Exceptions\InvalidConfigurationException
     */
    public function setConfiguration(EsiConfiguration $configuration): void
    {
        if (! $configuration->valid())
            throw new InvalidConfigurationException(
                'The configuration is empty/invalid values');

        $this->configuration = $configuration;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        if (! $this->logger) {
            $this->logger = is_string($this->configuration->logger) ?
                new $this->configuration->logger : $this->configuration->logger;
        }

        return $this->logger;
    }

    /**
     * @return \Psr\SimpleCache\CacheInterface
     */
    public function getCache(): CacheInterface
    {
        if (! $this->cache) {
            $this->cache = is_string($this->configuration->cache) ?
                new $this->configuration->cache : $this->configuration->cache;
        }

        return $this->cache;
    }

    /**
     * @return \Psr\Http\Client\ClientInterface
     */
    public function getHttpClient(): ClientInterface
    {
        if (! $this->http_client) {
            $this->http_client = is_string($this->configuration->http_client) ?
                new $this->configuration->http_client : $this->configuration->http_client;
        }

        return $this->http_client;
    }

    /**
     * @return \Psr\Http\Message\StreamFactoryInterface
     */
    public function getHttpStreamFactory(): StreamFactoryInterface
    {
        if (! $this->http_stream_factory) {
            $this->http_stream_factory = is_string($this->configuration->http_stream_factory) ?
                new $this->configuration->http_stream_factory : $this->configuration->http_stream_factory;
        }

        return $this->http_stream_factory;
    }

    /**
     * @return \Psr\Http\Message\RequestFactoryInterface
     */
    public function getHttpRequestFactory(): RequestFactoryInterface
    {
        if (! $this->http_request_factory) {
            $this->http_request_factory = is_string($this->configuration->http_request_factory) ?
                new $this->configuration->http_request_factory : $this->configuration->http_request_factory;
        }

        return $this->http_request_factory;
    }

    /**
     * Magic method to get the configuration from the configuration
     * property.
     *
     * @param  string  $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->configuration->$name;
    }

    /**
     * @param  string  $name
     * @param  mixed  $value
     * @return string
     */
    public function __set(string $name, mixed $value)
    {
        return $this->configuration->$name = $value;
    }
}
