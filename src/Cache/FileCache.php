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

namespace Seat\Eseye\Cache;

use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\Exceptions\CachePathException;

/**
 * Class FileCache.
 * @package Seat\Eseye\Cache
 */
class FileCache implements CacheInterface
{

    use HashesStrings;

    /**
     * @var string
     */
    protected $cache_path;

    /**
     * @var string
     */
    protected $results_filename = 'results.cache';

    /**
     * FileCache constructor.
     * @throws \Seat\Eseye\Exceptions\CachePathException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function __construct()
    {

        $this->cache_path = Configuration::getInstance()
            ->file_cache_location;

        // Ensure the cache directory is OK
        $this->checkCacheDirectory();
    }

    /**
     * @return bool
     * @throws \Seat\Eseye\Exceptions\CachePathException
     */
    public function checkCacheDirectory()
    {

        // Ensure the cache path exists
        if (! is_dir($this->cache_path) &&
            ! @mkdir($this->cache_path, 0775, true)
        ) {
            throw new CachePathException(
                'Unable to create cache directory ' . $this->cache_path);
        }

        // Ensure the cache directory is readable/writable
        if (! is_readable($this->cache_path) ||
            ! is_writable($this->cache_path)
        ) {

            if (! chmod($this->getCachePath(), 0775))
                throw new CachePathException(
                    $this->cache_path . ' must be readable and writable');
        }

        return true;
    }

    /**
     * @return string
     */
    public function getCachePath(): string
    {

        return $this->cache_path;
    }

    /**
     * @param string                             $uri
     * @param string                             $query
     * @param \Seat\Eseye\Containers\EsiResponse $data
     *
     * @return mixed|void
     */
    public function set(string $uri, string $query, EsiResponse $data)
    {

        $path = $this->buildRelativePath($this->safePath($uri), $query);

        // Create the subpath if that does not already exist
        if (! file_exists($path))
            @mkdir($path, 0775, true);

        // Dump the contents to file
        file_put_contents($path . $this->results_filename, serialize($data));
    }

    /**
     * @param string $path
     * @param string $query
     *
     * @return string
     */
    public function buildRelativePath(string $path, string $query = ''): string
    {

        // If the query string has data, hash it.
        if ($query != '')
            $query = $this->hashString($query);

        return rtrim(rtrim($this->cache_path, '/') . rtrim($path), '/') .
            '/' . $query . '/';
    }

    /**
     * @param string $uri
     *
     * @return string
     */
    public function safePath(string $uri): string
    {

        return preg_replace('/[^A-Za-z0-9\/]/', '', $uri);
    }

    /**
     * @param string $uri
     * @param string $query
     *
     * @return bool|mixed
     */
    public function has(string $uri, string $query = ''): bool
    {

        if ($status = $this->get($uri, $query))
            return true;

        return false;
    }

    /**
     * @param string $uri
     * @param string $query
     *
     * @return mixed
     */
    public function get(string $uri, string $query = '')
    {

        $path = $this->buildRelativePath($this->safePath($uri), $query);
        $cache_file_path = $path . $this->results_filename;

        // If we cant read from the cache, then just return false.
        if (! is_readable($cache_file_path))
            return false;

        // Get the data from the file and unserialize it
        $file = unserialize(file_get_contents($cache_file_path));

        // If the cached entry is expired, remove it.
        if ($file->expired()) {

            $this->forget($uri, $query);

            return false;
        }

        return $file;
    }

    /**
     * @param string $uri
     * @param string $query
     *
     * @return void
     */
    public function forget(string $uri, string $query = '')
    {

        $path = $this->buildRelativePath($uri, $query);
        $cache_file_path = $path . $this->results_filename;

        @unlink($cache_file_path);
    }
}
