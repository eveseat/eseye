<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015 to present Leon Jacobs
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

use DateInterval;
use Psr\SimpleCache\CacheInterface;
use Seat\Eseye\Configuration;
use Seat\Eseye\Exceptions\CachePathException;

/**
 * Class FileCache.
 *
 * @package Seat\Eseye\Cache
 */
class FileCache implements CacheInterface
{

    use CommonOperations, HashesStrings, ValidateCacheEntry;

    /**
     * @var string
     */
    protected string $cache_path;

    /**
     * @var string
     */
    protected string $results_filename = 'results.cache';

    /**
     * FileCache constructor.
     *
     * @throws \Seat\Eseye\Exceptions\CachePathException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function __construct()
    {
        $this->cache_path = Configuration::getInstance()->file_cache_location;

        // Ensure the cache directory is OK
        $this->checkCacheDirectory();
    }

    /**
     * @return bool
     *
     * @throws \Seat\Eseye\Exceptions\CachePathException
     */
    public function checkCacheDirectory(): bool
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
     * @param  string  $key
     * @param  mixed  $value
     * @param  int|\DateInterval|null  $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->validateCacheValue($value);
        $this->validateCacheKey($key, $uri_path, $uri_query);
        $path = $this->buildRelativePath($this->safePath($uri_path), $uri_query);

        // Create the subpath if that does not already exist
        if (! file_exists($path))
            @mkdir($path, 0775, true);

        // Dump the contents to file
        return file_put_contents($path . $this->results_filename, serialize($value)) !== false;
    }

    /**
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * @param  string  $key
     * @param  mixed|null  $default
     * @return \Seat\Eseye\Containers\EsiResponse
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateCacheKey($key, $uri_path, $uri_query);
        $path = $this->buildRelativePath($this->safePath($uri_path), $uri_query);
        $cache_file_path = $path . $this->results_filename;

        // If we cant read from the cache, then just return false.
        if (! is_readable($cache_file_path))
            return $default;

        // Get the data from the file and unserialize it
        $data = @unserialize(file_get_contents($cache_file_path));

        if ($data === false)
            return $default;

        // If the cached entry is expired and does not have any ETag, remove it.
        // The false case occur if a cache save is incomplete so the unserialize fails.
        if ($data === false || $data->expired() && ! $data->hasHeader('ETag')) {
            $this->delete($key);

            return $default;
        }

        return $data;
    }

    /**
     * @param  string  $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        $this->validateCacheKey($key, $uri_path, $uri_query);
        $path = $this->buildRelativePath($this->safePath($uri_path), $uri_query);
        $cache_file_path = $path . $this->results_filename;

        return @unlink($cache_file_path);
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        return $this->clearContainer($this->getCachePath());
    }

    /**
     * @param  string  $base_path
     * @return bool
     */
    private function clearContainer(string $base_path): bool
    {
        $paths = glob($base_path . '/*');

        foreach ($paths as $path) {
            $success = is_dir($path) ? $this->clearContainer($path) : @unlink($path);

            if (! $success)
                return false;
        }

        if ($base_path != $this->getCachePath()) {
            if (! @rmdir($base_path))
                return false;
        }

        return true;
    }

    /**
     * @param  string  $path
     * @param  string  $query
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
     * @param  string  $uri
     * @return string
     */
    public function safePath(string $uri): string
    {
        return preg_replace('/[^A-Za-z0-9\/]/', '', $uri);
    }
}
