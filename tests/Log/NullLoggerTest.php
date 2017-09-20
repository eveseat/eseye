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

use Seat\Eseye\Log\NullLogger;

class NullLoggerTest extends PHPUnit_Framework_TestCase
{

    protected $logger;

    public function setUp()
    {


        $this->logger = new NullLogger;
    }

    public function testNullLoggerIgnoresInfo()
    {

        $this->assertNull($this->logger->log('foo'));
    }

    public function testNullLoggerIgnoresDebug()
    {

        $this->assertNull($this->logger->debug('foo'));
    }

    public function testNullLoggerIgnoresWarning()
    {

        $this->assertNull($this->logger->warning('foo'));
    }

    public function testNullLoggerIgnoresErro()
    {

        $this->assertNull($this->logger->error('foo'));
    }

}
