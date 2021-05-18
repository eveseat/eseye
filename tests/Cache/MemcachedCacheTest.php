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

use PHPUnit\Framework\TestCase;
use Seat\Eseye\Cache\MemcachedCache;
use Seat\Eseye\Containers\EsiResponse;


class MemcachedCacheTest extends TestCase
{
    /**
     * @var \Seat\Eseye\Containers\EsiResponse
     */
    protected $esi_response_object;

    public function setUp(): void
    {
        $this->esi_response_object = new EsiResponse('', ['ETag' => 'W/"b3ef78b1064a27974cbf18270c1f126d519f7b467ba2e35ccb6f0819"'], 'now', 200);
    }

    public function testMemcachedCacheInstantiates()
    {
        $cache = new MemcachedCache();

        $this->assertInstanceOf(MemcachedCache::class, $cache);
    }

    public function testMemcachedCacheBuildsCacheKey()
    {
        $cache = new MemcachedCache();

        $key = $cache->buildCacheKey('/test', 'foo=bar');
        $this->assertEquals('eseye:b0f071c288f528954cddef0e1aa24df41de874aa', $key);
    }

    public function testMemcachedCacheSetsKey()
    {
        // Mock a memcache instance
        $instance = $this->getMockBuilder(stdClass::class)->addMethods(['set', 'get'])->getMock();
        $instance->expects($this->once())->method('set')->willReturn(true);
        $instance->expects($this->once())->method('get')->willReturn(serialize($this->esi_response_object));

        // Set the cache
        $cache = new MemcachedCache($instance);

        $cache->set('/foo', 'foo=bar', $this->esi_response_object);

        $this->assertEquals($this->esi_response_object, $cache->get('/foo', 'foo=bar'));
    }

    public function testMemcachedCacheForgetsKey()
    {

        // Mock a memcache instance
        $instance = $this->getMockBuilder(stdClass::class)->addMethods(['delete', 'get'])->getMock();
        $instance->expects($this->once())->method('delete')->willReturn(true);
        $instance->expects($this->once())->method('get')->willReturn(false);

        // Set the cache
        $cache = new MemcachedCache($instance);

        $cache->forget('/foo', 'foo=bar');

        $this->assertFalse($cache->get('/foo', 'foo=bar'));
    }
}
