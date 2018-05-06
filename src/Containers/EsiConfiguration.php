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

namespace Seat\Eseye\Containers;

use Monolog\Logger;
use Seat\Eseye\Cache\FileCache;
use Seat\Eseye\Fetchers\GuzzleFetcher;
use Seat\Eseye\Log\RotatingFileLogger;
use Seat\Eseye\Traits\ConstructsContainers;
use Seat\Eseye\Traits\ValidatesContainers;

/**
 * Class EsiConfiguration.
 * @package Seat\Eseye\Containers
 */
class EsiConfiguration extends AbstractArrayAccess
{

    use ConstructsContainers, ValidatesContainers;

    /**
     * @var array
     */
    protected $data = [
        'http_user_agent'            => 'Eseye Default Library',
        'datasource'                 => 'tranquility',

        // Fetcher
        'fetcher'                    => GuzzleFetcher::class,

        // Logging
        'logger'                     => RotatingFileLogger::class,
        'logger_level'               => Logger::INFO,
        'logfile_location'           => 'logs/',

        // Rotating Logger Details
        'log_max_files'              => 10,

        // Cache
        'cache'                      => FileCache::class,

        // File Cache
        'file_cache_location'        => 'cache/',

        // Redis Cache
        'redis_cache_location'       => 'tcp://127.0.0.1',
        'redis_cache_prefix'         => 'eseye:',

        // Memcached Cache
        'memcached_cache_host'       => '127.0.0.1',
        'memcached_cache_port'       => '11211',
        'memcached_cache_prefix'     => 'eseye:',
        'memcached_cache_compressed' => false,
    ];

}
