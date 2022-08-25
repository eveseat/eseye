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

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Seat\Eseye\Configuration;
use Seat\Eseye\Log\FileLogger;

class FileLoggerTest extends TestCase
{
    protected $root;

    protected FileLogger $logger;

    public function setUp(): void
    {
        // Set the file cache path in the config singleton
        $this->root = vfsStream::setup('logs/');
        Configuration::getInstance()->logfile_location = vfsStream::url('logs/');

        $this->logger = new FileLogger;
    }

    public function testFileLoggerWritesLog()
    {
        $this->logger->log('ERROR', 'foo');
        $logfile_content = $this->root->getChild('eseye.log')->getContent();

        $this->assertStringContainsString('eseye.ERROR: foo', $logfile_content);
    }

    public function testFileLoggerWritesLogNotice()
    {
        $this->logger->notice('foo');
        $logfile_content = $this->root->getChild('eseye.log')->getContent();

        $this->assertStringContainsString('eseye.NOTICE: foo', $logfile_content);
    }

    public function testFileLoggerWritesLogInfo()
    {
        $this->logger->info('foo');
        $logfile_content = $this->root->getChild('eseye.log')->getContent();

        $this->assertStringContainsString('eseye.INFO: foo', $logfile_content);
    }

    public function testFileLoggerSkipWritesLogDebugWithoutRequiredLevel()
    {
        $this->logger->debug('foo');
        $logfile_content = $this->root->getChild('eseye.log');

        $this->assertNull($logfile_content);
    }

    public function testFileLoggerWritesLogDebug()
    {
        Configuration::getInstance()->logger_level = 'DEBUG';

        // Init a new logger with the updated config
        $logger = new FileLogger;

        $logger->debug('foo');
        $logfile_content = $this->root->getChild('eseye.log')->getContent();

        $this->assertStringContainsString('eseye.DEBUG: foo', $logfile_content);
    }

    public function testFileLoggerWritesLogWarning()
    {
        $this->logger->warning('foo');
        $logfile_content = $this->root->getChild('eseye.log')->getContent();

        $this->assertStringContainsString('eseye.WARNING: foo', $logfile_content);
    }

    public function testFileLoggerWritesLogError()
    {
        $this->logger->error('foo');
        $logfile_content = $this->root->getChild('eseye.log')->getContent();

        $this->assertStringContainsString('eseye.ERROR: foo', $logfile_content);
    }

    public function testFileLoggerWritesLogCritical()
    {
        $this->logger->critical('foo');
        $logfile_content = $this->root->getChild('eseye.log')->getContent();

        $this->assertStringContainsString('eseye.CRITICAL: foo', $logfile_content);
    }

    public function testFileLoggerWritesLogAlert()
    {
        $this->logger->alert('foo');
        $logfile_content = $this->root->getChild('eseye.log')->getContent();

        $this->assertStringContainsString('eseye.ALERT: foo', $logfile_content);
    }

    public function testFileLoggerWritesLogEmergency()
    {
        $this->logger->emergency('foo');
        $logfile_content = $this->root->getChild('eseye.log')->getContent();

        $this->assertStringContainsString('eseye.EMERGENCY: foo', $logfile_content);
    }
}
