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

use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Exceptions\InvalidContainerDataException;

class EsiAuthenticationTest extends PHPUnit_Framework_TestCase
{

    protected $esi_authentication;

    public function setUp()
    {

        $this->esi_authentication = new EsiAuthentication;
    }

    public function testEsiAuthenticationInstantiation()
    {

        $this->assertInstanceOf(EsiAuthentication::class, $this->esi_authentication);
    }

    public function testFreshEsiAuthenticationInstanceIsNotValid()
    {

        $this->assertFalse($this->esi_authentication->valid());
    }

    public function testEsiAuthenticationCanAccessAsArrayKey()
    {

        $this->assertArrayHasKey('client_id', $this->esi_authentication);
    }

    public function testEsiAuthenticationCanAccessAsObjectProperty()
    {

        $client_id = $this->esi_authentication->client_id;
        $this->assertNull($client_id);
    }

    public function testCanSetAndAccessConfigurationValueAsArrayKey()
    {

        $authentication = new EsiAuthentication;
        $authentication['test'] = 'test';

        $this->assertEquals('test', $authentication['test']);
    }

    public function testCanSetAndAccessConfigurationValueAsObjectProperty()
    {

        $authentication = new EsiAuthentication;
        $authentication->test = 'test';

        $this->assertEquals('test', $authentication->test);
    }

    public function testEsiAuthenticationContainerConstructWithValuePasses()
    {

        $authentication = new EsiAuthentication([
            'client_id' => '123',
        ]);

        $this->assertInstanceOf(EsiAuthentication::class, $authentication);
    }

    public function testEsiAuthenticationContainerConstructWithUnknownKeyFails()
    {

        $this->expectException(InvalidContainerDataException::class);

        new EsiAuthentication([
            'foo' => 'bar',
        ]);
    }

    /**
     * @param $key The key to check for existence
     *
     * @dataProvider providerTestRequiredKeysExists
     */
    public function testRequiredKeysExists($key)
    {

        $authentication = new EsiAuthentication;
        $this->assertArrayHasKey($key, $authentication);
    }

    /**
     * Keys that _should_ exists in a new Configuration instance
     *
     * @return array
     */
    public function providerTestRequiredKeysExists()
    {

        return [
            ['client_id'],
            ['secret'],
            ['access_token'],
            ['refresh_token'],
            ['token_expires'],
            ['scopes'],
        ];
    }

    public function testEsiAuthenticationContainerSetRefreshToken()
    {

        $authentication = new EsiAuthentication;
        $authentication->setRefreshToken('REFRESH_TOKEN');

        $this->assertEquals('REFRESH_TOKEN', $authentication->refresh_token);
    }

}
