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

use Seat\Eseye\Containers\EsiConfiguration;
use Seat\Eseye\Exceptions\InvalidContainerDataException;

/**
 * Class EsiConfigurationTest
 */
class EsiConfigurationTest extends \PHPUnit_Framework_TestCase
{

    protected $esi_configuration;

    public function setUp()
    {

        $this->esi_configuration = new EsiConfiguration;
    }

    public function testEsiConfigurationInstantiation()
    {

        $instance = new EsiConfiguration;
        $this->assertInstanceOf(EsiConfiguration::class, $instance);
    }

    public function testFreshEsiConfigurationInstanceIsValid()
    {

        $this->assertTrue($this->esi_configuration->valid());
    }

    public function testEsiConfigurationCanAccessAsArrayKey()
    {

        $this->assertArrayHasKey('datasource', $this->esi_configuration);
    }

    public function testEsiConfigurationCanAccessAsObjectProperty()
    {

        $datasource = $this->esi_configuration->datasource;
        $this->assertEquals('tranquility', $datasource);
    }

    public function testCanSetAndAccessConfigurationValueAsArrayKey()
    {

        $configuration = new EsiConfiguration;
        $configuration['test'] = 'test';

        $this->assertEquals('test', $configuration['test']);
    }

    public function testCanSetAndAccessConfigurationValueAsObjectProperty()
    {

        $configuration = new EsiConfiguration;
        $configuration->test = 'test';

        $this->assertEquals('test', $configuration->test);
    }

    public function testEsiConfigurationContainerConstructWithValuePasses()
    {

        $configuration = new EsiConfiguration([
            'datasource' => 'tranquility',
        ]);

        $this->assertInstanceOf(EsiConfiguration::class, $configuration);
    }

    public function testEsiConfigurationContainerConstructWithUnknownKeyFails()
    {

        $this->expectException(InvalidContainerDataException::class);

        new EsiConfiguration([
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

        $configuration = new EsiConfiguration;
        $this->assertArrayHasKey($key, $configuration);
    }

    /**
     * Keys that _should_ exists in a new Configuration instance
     *
     * @return array
     */
    public function providerTestRequiredKeysExists()
    {

        return [
            ['http_user_agent'],
            ['datasource'],
            ['logger'],
            ['logger_level'],
            ['logfile_location'],
            ['cache'],
        ];
    }
}
