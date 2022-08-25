<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015 to 2022 Leon Jacobs
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
use Psr\Log\LoggerInterface;
use Seat\Eseye\Configuration;
use Stringable;

/**
 * Class FileLogger.
 *
 * @package Seat\Eseye\Log
 */
class FileLogger implements LoggerInterface
{

    /**
     * @var \Monolog\Logger
     */
    protected Logger $logger;

    /**
     * FileLogger constructor.
     *
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
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    /**
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    /**
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function error(string|Stringable $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    /**
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function info(string|Stringable $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    /**
     * @param $level
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
