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

use Seat\Eseye\Access\CheckAccess;
use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Eseye;
use Seat\Eseye\Exceptions\InvalidAuthencationException;
use Seat\Eseye\Exceptions\InvalidContainerDataException;
use Seat\Eseye\Log\LogInterface;

class EseyeTest extends PHPUnit_Framework_TestCase
{

    protected $esi;

    public function setUp()
    {

        $this->esi = new Eseye();
    }

    public function testEseyeInstantiation()
    {

        $this->assertInstanceOf(Eseye::class, $this->esi);
    }

    public function testEseyeInstantiateWithInvalidAuthenticationData()
    {

        $this->expectException(InvalidContainerDataException::class);

        $authentication = new EsiAuthentication([
            'foo' => 'bar',
        ]);
        new Eseye($authentication);
    }

    public function testEseyeInstantiateWithValidAuthenticationData()
    {

        $authentication = new EsiAuthentication([
            'client_id'     => 'SSO_CLIENT_ID',
            'secret'        => 'SSO_SECRET',
            'refresh_token' => 'CHARACTER_REFRESH_TOKEN',
        ]);
        new Eseye($authentication);
    }

    public function testEseyeSetNewInvalidAuthenticationData()
    {

        $this->expectException(InvalidContainerDataException::class);

        $authentication = new EsiAuthentication([
            'foo' => 'bar',
        ]);
        $this->esi->setAuthentication($authentication);
    }

    public function testEseyeSetNewValidAuthenticationData()
    {

        $authentication = new EsiAuthentication([
            'client_id'     => 'SSO_CLIENT_ID',
            'secret'        => 'SSO_SECRET',
            'access_token'  => 'ACCESS_TOKEN',
            'refresh_token' => 'CHARACTER_REFRESH_TOKEN',
            'token_expires' => '1970-01-01 00:00:00',
            'scopes'        => ['public'],
        ]);
        $this->esi->setAuthentication($authentication);
    }

    public function testEseyeGetAuthenticationBeforeSet()
    {

        $this->expectException(InvalidAuthencationException::class);

        $this->esi->getAuthentication();
    }

    public function testEseyeGetAuthenticationAfterSet()
    {

        $authentication = new EsiAuthentication([
            'client_id'     => 'SSO_CLIENT_ID',
            'secret'        => 'SSO_SECRET',
            'access_token'  => 'ACCESS_TOKEN',
            'refresh_token' => 'CHARACTER_REFRESH_TOKEN',
            'token_expires' => '1970-01-01 00:00:00',
            'scopes'        => ['public'],
        ]);
        $this->esi->setAuthentication($authentication);

        $this->assertInstanceOf(EsiAuthentication::class, $this->esi->getAuthentication());
    }

    public function testEseyeGetConfigurationInstance()
    {

        $this->assertInstanceOf(Configuration::class, $this->esi->getConfiguration());
    }

    public function testEseyeGetLogger()
    {

        $this->assertInstanceOf(LogInterface::class, $this->esi->getLogger());
    }

    public function testEseyeSetAccessChecker()
    {

        $access = $this->createMock(CheckAccess::class);

        $this->assertInstanceOf(Eseye::class, $this->esi->setAccessChecker($access));
    }

    public function testEseyeGetAccessCheckere()
    {

        $this->assertInstanceOf(CheckAccess::class, $this->esi->getAccesChecker());
    }

    public function testEseyeGetAndSetQueryString()
    {

        $object = $this->esi->setQueryString(['foo' => 'bar']);

        $this->assertInstanceOf(Eseye::class, $object);
        $this->assertEquals(['foo' => 'bar'], $this->esi->getQueryString());
    }

    public function testEseyeGetAndSetBody()
    {

        $object = $this->esi->setBody(['foo']);

        $this->assertInstanceOf(Eseye::class, $object);
        $this->assertEquals(['foo'], $this->esi->getBody());
    }

    public function testEseyeBuildingDataUri()
    {

        $uri = $this->esi->buildDataUri('/{foo}/', ['foo' => 'bar']);

        $this->assertEquals('https://esi.tech.ccp.is/latest/bar/?datasource=test',
            $uri->__toString());
    }

}
