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

namespace Seat\Eseye\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;
use Seat\Eseye\Configuration;

/**
 * Class MemcachedCache.
 *
 * @package Seat\Eseye\Cache
 */
class MemcachedCache implements CacheInterface
{
    use CommonOperations, HashesStrings, ValidateCacheEntry;

    /**
     * @var mixed
     */
    protected mixed $prefix;

    /**
     * @var bool
     */
    protected bool $is_memcached;

    /**
     * @var \Memcache|\Memcached|null
     */
    protected mixed $memcached = null;

    /**
     * @var int
     */
    protected int $flags = 0;

    /**
     * MemcachedCache constructor.
     *
     * @param  null  $instance
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
                $this->memcached->setOption(\Memcached::OPT_COMPRESSION, $configuration->memcached_cache_compressed);
            else
                $this->flags = ($configuration->memcached_cache_compressed) ? MEMCACHE_COMPRESSED : 0;
        }
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     * @param  int|\DateInterval|null  $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->validateCacheValue($value);
        $this->validateCacheKey($key, $uri_path, $uri_query);

        if ($this->is_memcached)
            return $this->memcached->set($this->buildCacheKey($uri_path, $uri_query), serialize($value), 0);
        else
            return $this->memcached->set($this->buildCacheKey($uri_path, $uri_query), serialize($value), $this->flags, 0);
    }

    /**
     * @param  string  $key
     * @param  mixed|null  $default
     * @return \Seat\Eseye\Containers\EsiResponse
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateCacheKey($key, $uri_path, $uri_query);

        $value = $this->memcached->get($this->buildCacheKey($uri_path, $uri_query));
        if ($value === false)
            return $default;

        $data = unserialize($value);

        if ($data === false)
            return $default;

        // If the cached entry is expired and does not have any ETag, remove it.
        if ($data->expired() && ! $data->hasHeader('ETag')) {
            $this->delete($key);

            return $default;
        }

        return $data;
    }

    /**
     * @param  string  $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        $this->validateCacheKey($key, $uri_path, $uri_query);

        return $this->memcached->delete($this->buildCacheKey($uri_path, $uri_query));
    }

    /**
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {

        return $this->memcached->get($this->buildCacheKey($key)) !== null;
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        return false;
    }

    /**
     * @param  string  $uri
     * @param  string  $query
     * @return string
     */
    public function buildCacheKey(string $uri, string $query = ''): string
    {
        if ($query != '')
            $query = $this->hashString($query);

        return $this->prefix . $this->hashString($uri . $query);
    }
}
