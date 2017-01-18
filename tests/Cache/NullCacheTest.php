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

use Seat\Eseye\Cache\NullCache;
use Seat\Eseye\Containers\EsiResponse;

class NullCacheTest extends PHPUnit_Framework_TestCase
{

    protected $null_cache;

    public function setUp()
    {

        $this->null_cache = new NullCache;
    }

    public function testNullCacheInstantiates()
    {

        $this->assertInstanceOf(NullCache::class, $this->null_cache);
    }

    public function testNullCacheSetsValue()
    {

        $esi_response = $this->createMock(EsiResponse::class);
        $return = $this->null_cache->set('/test', 'foo=bar', $esi_response);

        $this->assertNull($return);
    }

    public function testNullCacheGetsValue()
    {

        $this->assertFalse($this->null_cache->get('/test', 'foo=bar'));
    }

    public function testNullCacheForgetsValues()
    {

        $this->assertNull($this->null_cache->forget('/test', 'foo=bar'));
    }

    public function testNullCacheHasValue()
    {

        $this->assertFalse($this->null_cache->has('/test', 'foo=bar'));
    }

}
