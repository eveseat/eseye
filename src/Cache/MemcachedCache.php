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

namespace Seat\Eseye\Cache;

use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiResponse;

/**
 * Class MemcachedCache.
 * @package Seat\Eseye\Cache
 */
class MemcachedCache implements CacheInterface
{
    use HashesStrings;

    /**
     * @var mixed
     */
    protected $prefix;

    /**
     * @var bool
     */
    protected $is_memcached;

    /**
     * @var \Memcache
     */
    protected $memcached;

    /**
     * @var int
     */
    protected $flags;

    /**
     * MemcachedCache constructor.
     *
     * @param null $instance
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function __construct($instance = null)
    {

        if ($instance != null)
            $this->memcached = $instance;

        $this->is_memcached = class_exists('Memcached', false);

        $configuration = Configuration::getInstance();
        $this->prefix = $configuration->memcached_cache_prefix;

        if (is_null($this->memcached)) {
            if ($this->is_memcached)
                $this->memcached = new \Memcached();
            else
                $this->memcached = new \Memcache();

            $this->memcached->addServer($configuration->memcached_cache_host, $configuration->memcached_cache_port, 0);

            if ($this->is_memcached)
                $this->memcached->setOption(\Memcached::OPT_COMPRESSION, ($configuration->memcached_cache_compressed));
            else
                $this->flags = ($configuration->memcached_cache_compressed) ? MEMCACHE_COMPRESSED : 0;
        }

    }

    /**
     * @param string                             $uri
     * @param string                             $query
     * @param \Seat\Eseye\Containers\EsiResponse $data
     *
     * @return void
     */
    public function set(string $uri, string $query, EsiResponse $data)
    {

        if ($this->is_memcached)
            $this->memcached->set($this->buildCacheKey($uri, $query), serialize($data), 0);
        else
            $this->memcached->set($this->buildCacheKey($uri, $query), serialize($data), $this->flags, 0);
    }

    /**
     * @param string $uri
     * @param string $query
     *
     * @return string
     */
    public function buildCacheKey(string $uri, string $query = ''): string
    {

        if ($query != '')
            $query = $this->hashString($query);

        return $this->prefix . $this->hashString($uri . $query);
    }

    /**
     * @param string $uri
     * @param string $query
     *
     * @return mixed
     */
    public function get(string $uri, string $query = '')
    {

        $value = $this->memcached->get($this->buildCacheKey($uri, $query));
        if ($value === false)
            return false;

        $data = unserialize($value);

        if ($data->expired()) {
            $this->forget($uri, $query);

            return false;
        }

        return $data;
    }

    /**
     * @param string $uri
     * @param string $query
     *
     * @return mixed
     */
    public function forget(string $uri, string $query = '')
    {

        return $this->memcached->delete($this->buildCacheKey($uri, $query));
    }

    /**
     * @param string $uri
     * @param string $query
     *
     * @return bool|mixed
     */
    public function has(string $uri, string $query = ''): bool
    {

        return $this->memcached->get($this->buildCacheKey($uri, $query)) !== false;
    }
}
