<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015, 2016, 2017  Leon Jacobs
 * Copyright (C) 2017 HgAlexx
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
        // Memcached: change config for test if needed
        //$configuration = Seat\Eseye\Configuration::getInstance();
        //$configuration->memcached_host = '192.168.0.151';
        //$configuration->memcached_port = 11211;
        //$configuration->memcached_compressed = true;

        // Set the cache
        $this->memcached_cache = new MemcachedCache();

        $this->esi_response_object = new EsiResponse(new stdClass(), 'now', 200);
    }

    public function testMemcachedCacheInstantiates()
    {
        $this->assertInstanceOf(MemcachedCache::class, $this->memcached_cache);
    }

    public function testMemcachedCacheBuildsCacheKey()
    {
        $key = $this->memcached_cache->buildCacheKey('/test', 'foo=bar');
        $this->assertEquals('b0f071c288f528954cddef0e1aa24df41de874aa', $key);
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
