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

use Predis\Client;
use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiResponse;

/**
 * Class RedisCache.
 * @package Seat\Eseye\Cache
 */
class RedisCache implements CacheInterface
{

    use HashesStrings;

    /**
     * @var \Predis\Client
     */
    protected $redis;

    /**
     * RedisCache constructor.
     *
     * @param \Predis\Client $redis
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
     * @param string                             $uri
     * @param string                             $query
     * @param \Seat\Eseye\Containers\EsiResponse $data
     *
     * @return void
     */
    public function set(string $uri, string $query, EsiResponse $data)
    {

        $this->redis->set($this->buildCacheKey($uri, $query), serialize($data));
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

        return $this->hashString($uri . $query);
    }

    /**
     * @param string $uri
     * @param string $query
     *
     * @return mixed
     */
    public function get(string $uri, string $query = '')
    {

        if (! $this->has($uri, $query))
            return false;

        $data = unserialize($this->redis
            ->get($this->buildCacheKey($uri, $query)));

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
     * @return bool|mixed
     */
    public function has(string $uri, string $query = ''): bool
    {

        return $this->redis->exists($this->buildCacheKey($uri, $query));
    }

    /**
     * @param string $uri
     * @param string $query
     *
     * @return mixed
     */
    public function forget(string $uri, string $query = '')
    {

        return $this->redis->del([$this->buildCacheKey($uri, $query)]);
    }
}
