<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015, 2016, 2017, 2018  Leon Jacobs
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

namespace Seat\Eseye\Log;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Seat\Eseye\Configuration;

/**
 * Class FileLogger.
 * @package Seat\Eseye\Log
 */
class FileLogger implements LogInterface
{

    /**
     * @var \Monolog\Logger
     */
    protected $logger;

    /**
     * FileLogger constructor.
     * @throws \Exception
     */
    public function __construct()
    {

        // Get the configuration values
        $configuration = Configuration::getInstance();

        $formatter = new LineFormatter("[%datetime%] %channel%.%level_name%: %message%\n");
        $stream = new StreamHandler(
            rtrim($configuration->logfile_location, '/') . '/eseye.log',
            $configuration->logger_level
        );
        $stream->setFormatter($formatter);

        $this->logger = new Logger('eseye');
        $this->logger->pushHandler($stream);
    }

    /**
     * @param string $message
     *
     * @return mixed|void
     */
    public function log(string $message)
    {

        $this->logger->addInfo($message);
    }

    /**
     * @param string $message
     *
     * @return mixed|void
     */
    public function debug(string $message)
    {

        $this->logger->addDebug($message);
    }

    /**
     * @param string $message
     *
     * @return mixed|void
     */
    public function warning(string $message)
    {

        $this->logger->addWarning($message);
    }

    /**
     * @param string $message
     *
     * @return mixed|void
     */
    public function error(string $message)
    {

        $this->logger->addError($message);
    }
}
