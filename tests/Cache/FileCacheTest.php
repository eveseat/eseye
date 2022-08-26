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

namespace Seat\Tests\Cache;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Seat\Eseye\Cache\FileCache;
use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\Exceptions\CachePathException;

class FileCacheTest extends TestCase
{

    protected $root;

    protected FileCache $file_cache;

    public function setUp(): void
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

    public function testFileCacheCanRetrievePath()
    {
        $result = $this->file_cache->getCachePath();

        $this->assertEquals(Configuration::getInstance()->file_cache_location, $result);
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

        if (str_starts_with(PHP_OS, 'WIN'))
            $invalid_path = '/completely:invalid?path';
        else
            $invalid_path = '/completely/invalid/path';

        Configuration::getInstance()
            ->file_cache_location = $invalid_path;
        new FileCache();
    }

    public function testFileCacheCheckEntryDoesNotExist()
    {
        $result = $this->file_cache->has('https://example.tld');

        $this->assertFalse($result);
    }

    public function testFileCacheCheckEntryExists()
    {
        $response = new EsiResponse('', [], carbon()->addMinute(), 200);
        $this->file_cache->set('https://example.tld', $response);

        $result = $this->file_cache->has('https://example.tld');

        $this->assertTrue($result);
    }

    public function testFileCacheCanStoreMultipleEntries()
    {
        $responses = [
            'https://example.tld/foo' => new EsiResponse('', [], carbon()->addMinute(), 200),
            'https://example.tld/bar' => new EsiResponse('', [], carbon()->addMinute(), 200),
        ];

        $result = $this->file_cache->setMultiple($responses);

        $this->assertTrue($result);
    }

    public function testFileCacheCanRetrieveMultipleEntries()
    {
        $responses = [
            '/foo' => new EsiResponse('', [], carbon()->addMinute(), 200),
            '/bar' => new EsiResponse('', [], carbon()->addMinute(), 200),
        ];

        $this->file_cache->setMultiple($responses);

        $result = $this->file_cache->getMultiple(array_keys($responses));

        foreach ($result as $response) {
            $this->assertNotNull($response);
        }
    }

    public function testFileCacheCanDeleteEntry()
    {
        $response = new EsiResponse('', [], carbon()->addMinute(), 200);

        $this->file_cache->set('/foo', $response);

        $result = $this->file_cache->delete('/foo');

        $this->assertTrue($result);
    }

    public function testFileCacheCannotDeleteEntry()
    {
        $result = $this->file_cache->delete('/dummy');

        $this->assertFalse($result);
    }

    public function testFileCacheCanDeleteMultipleEntries()
    {
        $responses = [
            '/foo' => new EsiResponse('', [], carbon()->addMinute(), 200),
            '/bar' => new EsiResponse('', [], carbon()->addMinute(), 200),
        ];

        $this->file_cache->setMultiple($responses);

        $result = $this->file_cache->deleteMultiple(array_keys($responses));

        $this->assertTrue($result);
    }

    public function testFileCacheCannotDeleteMultipleEntries()
    {
        $responses = [
            '/foo' => new EsiResponse('', [], carbon()->addMinute(), 200),
            '/bar' => new EsiResponse('', [], carbon()->addMinute(), 200),
        ];

        $this->file_cache->set('/foo', $responses['/foo']);

        $result = $this->file_cache->deleteMultiple(array_keys($responses));

        $this->assertFalse($result);
    }

    public function testFileCacheCanClearStorage()
    {
        $responses = [
            '/foo' => new EsiResponse('', [], carbon()->addMinute(), 200),
            '/bar' => new EsiResponse('', [], carbon()->addMinute(), 200),
        ];

        $this->file_cache->setMultiple($responses);

        $result = $this->file_cache->clear();

        $this->assertTrue($result);
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
    public function providerTestFileCacheSafePathValues(): array
    {
        return [
            ['A/B/C', 'A/B/C'],
            ['\'A/B/C', 'A/B/C'],
            ['`A/B/C`', 'A/B/C'],
            ['|&*A%/$B!/C', 'A/B/C'],
        ];
    }

}
