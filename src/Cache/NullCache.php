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

namespace Seat\Eseye\Cache;


use Seat\Eseye\Containers\EsiResponse;

/**
 * Class NullCache
 * @package Seat\Eseye\Cache
 */
class NullCache implements CacheInterface
{

    /**
     * @param string                             $uri
     * @param \Seat\Eseye\Containers\EsiResponse $data
     *
     * @return mixed
     */
    public function set(string $uri, EsiResponse $data)
    {
    }

    /**
     *
     * @param string $uri
     *
     * @return mixed
     */
    public function get(string $uri)
    {

        return false;
    }

    /**
     * @param string $uri
     *
     * @return mixed
     */
    public function forget(string $uri)
    {

    }

    /**
     * @param string $uri
     *
     * @return mixed
     */
    public function has(string $uri): bool
    {

        return false;
    }
}