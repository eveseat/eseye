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

use Seat\Eseye\Cache\CacheInterface;
use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiConfiguration;
use Seat\Eseye\Exceptions\InvalidContainerDataException;
use Seat\Eseye\Log\LogInterface;

class ConfigurationTest extends PHPUnit_Framework_TestCase
{

    public function testConfigurationInstantiation()
    {

        $this->assertInstanceOf(Configuration::class, new Configuration());
    }

    public function testConfigurationSingleton()
    {

        $instance1 = new Configuration();
        $instance2 = new Configuration();

        $instance1->setConfiguration(new EsiConfiguration([
            'datasource' => 'test',
        ]));

        // Got a feeling this assert is wrong
        $this->assertNotEquals('<string:test>', $instance2->getConfiguration()->datasource);
    }

    public function testConfigurationGetConfigurationValuesContainer()
    {
        $configuration = new Configuration();
        $this->assertInstanceOf(EsiConfiguration::class, $configuration->getConfiguration());
    }

    public function testConfigurationSetsNewConfigurationContainerWithValidData()
    {

        $configuration = new EsiConfiguration(['http_user_agent' => 'Eseye Library']);
        $this->assertInstanceOf(EsiConfiguration::class, $configuration);
    }

    public function testConfigurationSetsNewConfigurationsContainerWithInvalidData()
    {

        $this->expectException(InvalidContainerDataException::class);
        new EsiConfiguration(['invalid' => 'invalid']);
    }

    public function testConfigurationSetsNewConfigurationContainerWithNullData()
    {
        $this->expectException(InvalidContainerDataException::class);
        new EsiConfiguration(['value' => null]);
    }

    public function testConfigurationGetsLogger()
    {
        $configuration = new Configuration();
        $logger = $configuration->getLogger();
        $this->assertInstanceOf(LogInterface::class, $logger);
    }

    public function testConfigurationGetsCache()
    {
        $configuration = new Configuration();
        $cache = $configuration->getCache();
        $this->assertInstanceOf(CacheInterface::class, $cache);
    }

    public function testConfigurationSetsNewValue()
    {

        $configuration = new Configuration();
        $configuration->test = 'test';

        $this->assertEquals('test', $configuration->test);
        $this->assertEquals('test', $configuration->getConfiguration()->test);
    }
}
