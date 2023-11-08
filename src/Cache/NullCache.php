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
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use Seat\Eseye\Containers\EsiResponse;

/**
 * Class NullCache.
 *
 * @package Seat\Eseye\Cache
 */
class NullCache implements CacheInterface
{
    /**
     * @param  string  $key
     * @param  mixed|null  $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     * @param  int|\DateInterval|null  $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        if (! $value instanceof EsiResponse)
            throw new InvalidArgumentException('An EsiResponse object was expected as cache value.');

        return false;
    }

    /**
     * @param  string  $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        return true;
    }

    /**
     * @param  iterable  $keys
     * @param  mixed|null  $default
     * @return iterable
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $results = [];

        foreach ($keys as $key)
            $results[$key] = $default;

        return $results;
    }

    /**
     * @param  iterable  $values
     * @param  int|\DateInterval|null  $ttl
     * @return bool
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        return false;
    }

    /**
     * @param  iterable  $keys
     * @return bool
     */
    public function deleteMultiple(iterable $keys): bool
    {
        return true;
    }

    /**
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return false;
    }
}
