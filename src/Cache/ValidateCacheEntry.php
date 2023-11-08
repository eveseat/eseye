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

use InvalidArgumentException;
use Seat\Eseye\Containers\EsiResponse;

trait ValidateCacheEntry
{
    /**
     * @param  mixed  $value
     * @return void
     */
    public function validateCacheValue(mixed $value): void
    {
        if (! $value instanceof EsiResponse)
            throw new InvalidArgumentException('An EsiResponse object was expected as cache value.');
    }

    /**
     * @param  string  $key
     * @param  string|null  $path
     * @param  string|null  $query
     * @return void
     */
    public function validateCacheKey(string $key, ?string &$path, ?string &$query): void
    {
        $path = parse_url($key, PHP_URL_PATH);
        $query = parse_url($key, PHP_URL_QUERY);

        if ($path === false)
            throw new InvalidArgumentException('A valid URI was expected as cache key.');

        if ($query === false)
            throw new InvalidArgumentException('A valid URI was expected as cache key.');

        if ($path === null)
            $path = '/';

        if ($query === null)
            $query = '';
    }
}
