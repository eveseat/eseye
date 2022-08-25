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

use Psr\Log\LoggerInterface;
use Stringable;

/**
 * Class NullLogger.
 *
 * @package Seat\Eseye\Log
 */
class NullLogger implements LoggerInterface
{
    /**
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function emergency(Stringable|string $message, array $context = []): void
    {
    }

    /**
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function alert(Stringable|string $message, array $context = []): void
    {
    }

    /**
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function critical(Stringable|string $message, array $context = []): void
    {
    }

    /**
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function error(Stringable|string $message, array $context = []): void
    {
    }

    /**
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function warning(Stringable|string $message, array $context = []): void
    {
    }

    /**
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function notice(Stringable|string $message, array $context = []): void
    {
    }

    /**
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function info(Stringable|string $message, array $context = []): void
    {
    }

    /**
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function debug(Stringable|string $message, array $context = []): void
    {
    }

    /**
     * @param $level
     * @param  string|\Stringable  $message
     * @param  array  $context
     * @return void
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
    }
}
