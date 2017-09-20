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

use org\bovigo\vfs\vfsStream;
use Seat\Eseye\Cache\FileCache;
use Seat\Eseye\Configuration;
use Seat\Eseye\Exceptions\CachePathException;

class FileCacheTest extends PHPUnit_Framework_TestCase
{

    protected $root;

    protected $file_cache;

    public function setUp()
    {

        // Set the file cache path in the config singleton
        $this->root = vfsStream::setup('cache');
        Configuration::getInstance()->file_cache_location = vfsStream::url('cache');

        $this->file_cache = new FileCache;
    }

    public function testFileCacheCanInstantiate()
    {

        $this->assertInstanceOf(FileCache::class, new FileCache);
    }

    public function testFileCacheCheckCacheDirectory()
    {

        $this->assertTrue($this->file_cache->checkCacheDirectory());
    }

    public function testFileCacheBuildsRelativePathWithoutQueryString()
    {

        $path = $this->file_cache->buildRelativePath('/test');

        $this->assertEquals('vfs://cache/test//', $path);
    }

    public function testFileCacheBuildsRelativePathWithQueryString()
    {

        $path = $this->file_cache->buildRelativePath('/test', 'foo=bar');

        $this->assertEquals('vfs://cache/test/2fb8f40115dd1e695cbe23d4f97ce5b1fb697eee/', $path);
    }

    public function testFileCacheFailsCreatingDirectoryOnInvalidPath()
    {

        $this->expectException(CachePathException::class);

        if (substr(PHP_OS, 0, 3) == 'WIN')
            $invalid_path = '/completely:invalid?path';
        else
            $invalid_path = '/completely/invalid/path';

        Configuration::getInstance()
            ->file_cache_location = $invalid_path;
        new FileCache();
    }

    /**
     * @param $input
     * @param $output
     *
     * @dataProvider providerTestFileCacheSafePathValues
     */
    public function testFileCacheSafePathValues($input, $output)
    {

        $result = $this->file_cache->safePath($input);

        $this->assertEquals($output, $result);
    }

    /**
     * @return array
     */
    public function providerTestFileCacheSafePathValues()
    {

        return [
            ['A/B/C', 'A/B/C'],
            ['\'A/B/C', 'A/B/C'],
            ['`A/B/C`', 'A/B/C'],
            ['|&*A%/$B!/C', 'A/B/C'],
        ];
    }

}
