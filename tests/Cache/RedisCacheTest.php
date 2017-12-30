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

use Predis\Client;
use Seat\Eseye\Cache\RedisCache;
use Seat\Eseye\Containers\EsiResponse;

class RedisCacheTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var RedisCache
     */
    protected $redis_cache;

    protected $esi_response_object;

    public function setUp()
    {

        $redis = $this->createMock(Client::class);

        // Set the cache
        $this->redis_cache = new RedisCache($redis);
        $this->esi_response_object = new EsiResponse('', [], 'now', 200);
    }

    public function testRedisCacheInstantiates()
    {

        $this->assertInstanceOf(RedisCache::class, $this->redis_cache);
    }

    public function testRedisCacheInstantiatesWithoutArgument()
    {

        $this->assertInstanceOf(RedisCache::class, new RedisCache);
    }

    public function testRedisCacheBuildsCacheKey()
    {

        $key = $this->redis_cache->buildCacheKey('/test', 'foo=bar');
        $this->assertEquals('b0f071c288f528954cddef0e1aa24df41de874aa', $key);
    }

    public function testRedisCacheSetsKey()
    {

        $this->redis_cache->set('/foo', 'foo=bar', $this->esi_response_object);
    }

    public function testRedisCacheForgetsKey()
    {

        $this->redis_cache->forget('/foo', 'foo=bar');
    }

}
