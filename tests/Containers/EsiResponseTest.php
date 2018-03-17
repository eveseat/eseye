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

use Seat\Eseye\Containers\EsiResponse;

class EsiResponseTest extends PHPUnit_Framework_TestCase
{

    protected $esi_response;

    protected $headers;

    public function setUp()
    {

        // Sample data to work with
        $data = json_encode([
            'name'    => 'Foo',
            'details' => [
                'age'   => 40,
                'human' => 'yes',
            ],
        ]);

        // Sample response headers
        $this->headers = $headers = [
            "Access-Control-Allow-Credentials" => [
                0 => "true",
            ],
            "Access-Control-Allow-Headers"     => [
                0 => "Content-Type,Authorization,X-User-Agent",
            ],
            "Content-Type"                     => [
                0 => "application/json",
            ],
            "Expires"                          => [
                0 => "Sat, 30 Dec 2017 09:00:32 GMT",
            ],

            "Strict-Transport-Security" => [
                0 => "max-age=31536000",
            ],
            "X-Esi-Error-Limit-Remain"  => [
                0 => "64",
            ],
            "X-Esi-Error-Limit-Reset"   => [
                0 => "52",
            ],
            "X-Pages"                   => [
                0 => "4",
            ],
            "Date"                      => [
                0 => "Sat, 30 Dec 2017 08:23:08 GMT",
            ],
        ];

        $this->esi_response = new EsiResponse($data, $headers, 'now', 200);
    }

    public function testEsiResponseInstantiation()
    {

        $this->assertInstanceOf(EsiResponse::class, $this->esi_response);
    }

    public function testEsiResponseTestPayloadIsExpired()
    {

        $this->assertTrue($this->esi_response->expired());
    }

    public function testEsiResponseTestPayloadIsNotExpired()
    {

        $data = json_encode(['foo' => 'bar']);
        $esi = new EsiResponse($data, [], '3000-01-01 00:00:00', 200);

        $this->assertFalse($esi->expired());
    }

    public function testEsiResponseDoesNotHaveError()
    {

        $this->assertNull($this->esi_response->error());
    }

    public function testEsiResponseDoesHaveError()
    {

        $data = json_encode(['error' => 'Test Error']);
        $esi = new EsiResponse($data, [], 'now', 500);

        $this->assertEquals('Test Error', $esi->error());
    }

    public function testEsiResponseDoesHaveErrorAndDescription()
    {

        $data = json_encode(['error' => 'Test Error', 'error_description' => 'Test Description']);
        $esi = new EsiResponse($data, [], 'now', 500);

        $this->assertEquals('Test Error: Test Description', $esi->error());
    }

    public function testEsiResponseCanGetErrorCode()
    {

        $this->assertEquals(200, $this->esi_response->getErrorCode());
    }

    public function testEsiResponseCanGetDataValue()
    {

        $this->assertEquals('Foo', $this->esi_response->name);
    }

    public function testEsiResponseCanGetNestedDataValue()
    {

        $this->assertEquals('yes', $this->esi_response->details->human);
    }

    public function testEsiResponseCanGetRawDataFromContainer()
    {

        $this->assertEquals('{"name":"Foo","details":{"age":40,"human":"yes"}}',
            $this->esi_response->raw);
    }

    public function testEsiResponseCanGetRawResponseHeaders()
    {

        $this->assertEquals($this->headers, $this->esi_response->raw_headers);
    }

    public function testEsiResponseCanGetParseHeaderValue()
    {

        $this->assertEquals('Content-Type,Authorization,X-User-Agent',
            $this->esi_response->headers['Access-Control-Allow-Headers']);
    }

    public function testEsiResponseCanGetParsedPagesFromHeaders()
    {

        $this->assertEquals(4, $this->esi_response->pages);
    }

    public function testEsiResponseCanGetParsedErrorLomitFromHeaders()
    {

        $this->assertEquals(64, $this->esi_response->error_limit);
    }

    public function testEsiResponseIsNotCachedByDefault()
    {

        $this->assertFalse($this->esi_response->isCachedLoad());
    }

    public function testEsiResponseMarksResponseAsCached()
    {

        $this->esi_response->setIsCachedload();
        $this->assertTrue($this->esi_response->isCachedLoad());
    }
}
