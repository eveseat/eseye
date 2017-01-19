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

use Seat\Eseye\Access\CheckAccess;
use Seat\Eseye\Configuration;
use Seat\Eseye\Log\NullLogger;

class CheckAccessTest extends PHPUnit_Framework_TestCase
{

    protected $check_access;

    public function setUp()
    {

        $this->check_access = new CheckAccess;
    }

    public function testCheckAccessObjectInstantiation()
    {

        $this->assertInstanceOf(CheckAccess::class, $this->check_access);
    }

    public function testCheckAccessCanShouldGrantAccess()
    {

        $scopes = [
            'esi-assets.read_assets.v1',
        ];
        $result = $this->check_access->can('get', '/characters/{character_id}/assets/', $scopes);

        $this->assertTrue($result);
    }

    public function testCheckAccessCanShouldDenyAccess()
    {


        $scopes = [
            'esi-assets.read_assets.v1',
        ];
        $result = $this->check_access->can('get', '/characters/{character_id}/bookmarks/', $scopes);

        $this->assertFalse($result);
    }

    public function testCheckAccessCanShouldAllowPublicOnlyCall()
    {

        $result = $this->check_access->can('get', '/alliances/', []);

        $this->assertTrue($result);
    }

    public function testCheckAccessShouldAllowAccessToUnknownUri()
    {

        // Disable logging.
        Configuration::getInstance()->logger = NullLogger::class;

        $result = $this->check_access->can('get', '/invalid/uri', []);

        $this->assertTrue($result);
    }

}
