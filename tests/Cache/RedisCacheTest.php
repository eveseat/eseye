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

use M6Web\Component\RedisMock\RedisMockFactory;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Seat\Eseye\Cache\RedisCache;
use Seat\Eseye\Containers\EsiResponse;

class RedisCacheTest extends TestCase
{

    /**
     * @var RedisCache
     */
    protected $redis_cache;

    protected $esi_response_object;

    public function setUp(): void
    {

        $factory = new RedisMockFactory();
        $class = $factory->getAdapterClass(Client::class, true);
        $redis = new $class();

        // Set the cache
        $this->redis_cache = new RedisCache($redis);
        $this->esi_response_object = new EsiResponse('', ['ETag' => 'W/"b3ef78b1064a27974cbf18270c1f126d519f7b467ba2e35ccb6f0819"'], 'now', 200);
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

        $this->assertEquals($this->esi_response_object, $this->redis_cache->get('/foo', 'foo=bar'));
    }

    public function testRedisCacheForgetsKey()
    {

        $this->redis_cache->forget('/foo', 'foo=bar');

        $this->assertFalse($this->redis_cache->has('/foo', 'foo=bar'));
    }

}
