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

use Carbon\Carbon;
use DateInterval;
use InvalidArgumentException;
use Predis\Client;
use Psr\SimpleCache\CacheInterface;
use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiResponse;

/**
 * Class RedisCache.
 *
 * @package Seat\Eseye\Cache
 */
class RedisCache implements CacheInterface
{
    use CommonOperations, HashesStrings, ValidateCacheEntry;

    /**
     * @var \Predis\Client
     */
    protected Client $redis;

    /**
     * RedisCache constructor.
     *
     * @param  \Predis\Client|null  $redis
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function __construct(Client $redis = null)
    {
        // If we didn't get a Redis instance in the constructor,
        // build a new one.
        if (is_null($redis)) {

            $configuration = Configuration::getInstance();

            $this->redis = new Client($configuration->redis_cache_location, [
                'prefix' => $configuration->redis_cache_prefix,
            ]);

            return;
        }

        $this->redis = $redis;
    }

    /**
     * @param  string  $key
     * @param  \Seat\Eseye\Containers\EsiResponse  $value
     * @param  int|\DateInterval|null  $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        if (! $value instanceof EsiResponse)
            throw new InvalidArgumentException('An EsiResponse object was expected as cache value.');

        $this->validateCacheKey($key, $uri_path, $uri_query);

        switch (true) {
            case $ttl == null:
                $this->redis->set($this->buildCacheKey($uri_path, $uri_query), serialize($value));

                break;
            case $ttl instanceof DateInterval:
                $now = Carbon::now('UTC');
                $expires = $now->clone()->add($ttl);

                $this->redis->setex($this->buildCacheKey($uri_path, $uri_query), $now->diffInSeconds($expires), serialize($value));

                break;
            default:
                $this->redis->setex($this->buildCacheKey($uri_path, $uri_query), $ttl, serialize($value));
        }

        return true;
    }

    /**
     * @param  string  $key
     * @param  mixed|null  $default
     * @return \Seat\Eseye\Containers\EsiResponse
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (! $this->has($key))
            return $default;

        $this->validateCacheKey($key, $uri_path, $uri_query);

        $data = unserialize($this->redis->get($this->buildCacheKey($uri_path, $uri_query)));

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
    public function has(string $key): bool
    {
        $this->validateCacheKey($key, $uri_path, $uri_query);

        return $this->redis->exists($this->buildCacheKey($uri_path, $uri_query));
    }

    /**
     * @param  string  $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        $this->validateCacheKey($key, $uri_path, $uri_query);

        return $this->redis->del([$this->buildCacheKey($uri_path, $uri_query)]) !== 0;
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

        return $this->hashString($uri . $query);
    }
}
