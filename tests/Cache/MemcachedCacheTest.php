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

use Seat\Eseye\Cache\MemcachedCache;
use Seat\Eseye\Containers\EsiResponse;


class MemcachedCacheTest extends PHPUnit_Framework_TestCase
{
    /*
     * @var MemcachedCache
     */
    protected $memcached_cache;

    protected $esi_response_object;

    public function setUp()
    {

        $is_memcached = class_exists('Memcached', false);
        if ($is_memcached)
            $instance = $this->createMock(\Memcached::class);
        else
            $instance = $this->createMock(\Memcache::class);

        // Set the cache
        $this->memcached_cache = new MemcachedCache($instance);

        $this->esi_response_object = new EsiResponse('', [], 'now', 200);
    }

    public function testMemcachedCacheInstantiates()
    {

        $this->assertInstanceOf(MemcachedCache::class, $this->memcached_cache);
    }

    public function testMemcachedCacheBuildsCacheKey()
    {

        $key = $this->memcached_cache->buildCacheKey('/test', 'foo=bar');
        $this->assertEquals('eseye:b0f071c288f528954cddef0e1aa24df41de874aa', $key);
    }

    public function testMemcachedCacheSetsKey()
    {

        $this->memcached_cache->set('/foo', 'foo=bar', $this->esi_response_object);
    }

    public function testMemcachedCacheForgetsKey()
    {

        $this->memcached_cache->forget('/foo', 'foo=bar');
    }
}
