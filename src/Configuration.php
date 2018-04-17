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

use Seat\Eseye\Cache\CacheInterface;
use Seat\Eseye\Containers\EsiConfiguration;
use Seat\Eseye\Exceptions\InvalidConfigurationException;
use Seat\Eseye\Log\LogInterface;

/**
 * Class Configuration.
 * @package Seat\Eseye
 */
class Configuration
{

    /**
     * @var Configuration
     */
    private static $instance;

    /**
     * @var LogInterface
     */
    protected $logger;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var EsiConfiguration
     */
    protected $configuration;

    /**
     * Configuration constructor.
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function __construct()
    {

        $this->configuration = new EsiConfiguration;
    }

    /**
     * @return \Seat\Eseye\Configuration
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public static function getInstance(): self
    {

        if (is_null(self::$instance))
            self::$instance = new self();

        return self::$instance;
    }

    /**
     * @return \Seat\Eseye\Containers\EsiConfiguration
     */
    public function getConfiguration()
    {

        return $this->configuration;
    }

    /**
     * @param \Seat\Eseye\Containers\EsiConfiguration $configuration
     *
     * @throws \Seat\Eseye\Exceptions\InvalidConfigurationException
     */
    public function setConfiguration(EsiConfiguration $configuration)
    {

        if (! $configuration->valid())
            throw new InvalidConfigurationException(
                'The configuration is empty/invalid values');

        $this->configuration = $configuration;
    }

    /**
     * @return \Seat\Eseye\Log\LogInterface
     */
    public function getLogger(): LogInterface
    {

        if (! $this->logger)
            $this->logger = new $this->configuration->logger;

        return $this->logger;
    }

    /**
     * @return \Seat\Eseye\Cache\CacheInterface
     */
    public function getCache(): CacheInterface
    {

        if (! $this->cache)
            $this->cache = new $this->configuration->cache;

        return $this->cache;
    }

    /**
     * Magic method to get the configuration from the configuration
     * property.
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {

        return $this->configuration->$name;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return string
     */
    public function __set(string $name, string $value)
    {

        return $this->configuration->$name = $value;
    }
}
