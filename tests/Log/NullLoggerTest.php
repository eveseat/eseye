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

namespace Seat\Tests\Log;

use Mockery;
use PHPUnit\Framework\TestCase;
use Seat\Eseye\Configuration;
use Seat\Eseye\Log\NullLogger;

class NullLoggerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected $logger;

    public function setUp(): void
    {
        Configuration::getInstance()->logger_level = 'DEBUG';
        $this->logger = Mockery::mock(NullLogger::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testNullLoggerIgnoresInfo()
    {
        $this->logger->shouldReceive('log')->with('info', 'foo');

        $this->logger->log('info', 'foo');
    }

    public function testNullLoggerIgnoresNotice()
    {
        $this->logger->shouldReceive('notice')->with('foo');

        $this->logger->notice('foo');
    }

    public function testNullLoggerIgnoresDebug()
    {
        $this->logger->shouldReceive('debug')->with('foo');

        $this->logger->debug('foo');
    }

    public function testNullLoggerIgnoresWarning()
    {
        $this->logger->shouldReceive('warning')->with('foo');

        $this->logger->warning('foo');
    }

    public function testNullLoggerIgnoresError()
    {
        $this->logger->shouldReceive('error')->with('foo');

        $this->logger->error('foo');
    }

    public function testNullLoggerIgnoresCritical()
    {
        $this->logger->shouldReceive('critical')->with('foo');

        $this->logger->critical('foo');
    }

    public function testNullLoggerIgnoresAlert()
    {
        $this->logger->shouldReceive('alert')->with('foo');

        $this->logger->alert('foo');
    }

    public function testNullLoggerIgnoresEmergency()
    {
        $this->logger->shouldReceive('emergency')->with('foo');

        $this->logger->emergency('foo');
    }
}
