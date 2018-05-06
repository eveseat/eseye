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

use Monolog\Logger;
use org\bovigo\vfs\vfsStream;
use Seat\Eseye\Configuration;
use Seat\Eseye\Log\RotatingFileLogger;

class RotatingFileLoggerTest extends PHPUnit_Framework_TestCase
{

    protected $root;

    protected $logger;

    protected $logfile_name;

    public function setUp()
    {

        // Set the file cache path in the config singleton
        $this->root = vfsStream::setup('logs');
        Configuration::getInstance()->logfile_location = $this->root->url();
        Configuration::getInstance()->logger_level = 'info';

        $this->logger = new RotatingFileLogger;

        # Shitty hack to get the filename to expect. Format: eseye-2018-05-06.log
        $this->logfile_name = 'eseye-' . date('Y-m-d') . '.log';
    }

    public function testFileLoggerWritesLogInfo()
    {

        $this->logger->log('foo');
        $logfile_content = $this->root->getChild($this->logfile_name)->getContent();

        $this->assertContains('eseye.INFO: foo', $logfile_content);
    }

    public function testFileLoggerSkipWritesLogDebugWithoutRequiredLevel()
    {

        $this->logger->debug('foo');
        $logfile_content = $this->root->getChild($this->logfile_name);

        $this->assertNull($logfile_content);
    }

    public function testFileLoggerWritesLogDebug()
    {

        Configuration::getInstance()->logger_level = Logger::DEBUG;

        // Init a new logger with the updated config
        $logger = new RotatingFileLogger;

        $logger->debug('foo');
        $logfile_content = $this->root->getChild($this->logfile_name)->getContent();

        $this->assertContains('eseye.DEBUG: foo', $logfile_content);
    }

    public function testFileLoggerWritesLogWarning()
    {

        $this->logger->warning('foo');
        $logfile_content = $this->root->getChild($this->logfile_name)->getContent();

        $this->assertContains('eseye.WARNING: foo', $logfile_content);
    }

    public function testFileLoggerWritesLogError()
    {

        $this->logger->error('foo');
        $logfile_content = $this->root->getChild($this->logfile_name)->getContent();

        $this->assertContains('eseye.ERROR: foo', $logfile_content);
    }

}
