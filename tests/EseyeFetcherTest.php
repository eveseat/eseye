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
use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\EseyeFetcher;
use Seat\Eseye\Exceptions\InvalidAuthencationException;

class EseyeFetcherTest extends PHPUnit_Framework_TestCase
{

    protected $fetcher;

    public function setUp()
    {

        $this->fetcher = new EseyeFetcher;
    }

    public function testEseyeFetcherInstantiation()
    {

        $this->assertInstanceOf(EseyeFetcher::class, $this->fetcher);
    }

    public function testEseyeFetcherStripRefreshTokenFromUrl()
    {

        $url = 'https://esi.url/oauth?type=refresh_token&refresh_token=foo';
        $stripped = $this->fetcher->stripRefreshTokenValue($url);

        $this->assertEquals('https://esi.url/oauth?type=refresh_token', $stripped);
    }

    public function testEseyeFetcherMakeEsiResponseContainer()
    {

        $response = json_decode(json_encode(['response' => 'ok']));

        $container = $this->fetcher->makeEsiResponse($response, 'now', 200);

        $this->assertInstanceOf(EsiResponse::class, $container);
    }

    public function testEseyeFetcherGetAuthenticationWhenNoneSet()
    {

        $authentication = $this->fetcher->getAuthentication();

        $this->assertNull($authentication);
    }

    public function testEseyeFetcherGetAuthenticationWhenSettingAuthentication()
    {

        $fetcher = new EseyeFetcher(new EsiAuthentication([
            'client_id' => 'foo',
        ]));

        $this->assertInstanceOf(EsiAuthentication::class, $fetcher->getAuthentication());
    }

    public function testEseyeFetcherGetPublicScopeWithoutAuthentication()
    {

        $scopes = $this->fetcher->getAuthenticationScopes();

        $this->assertEquals(1, count($scopes));
    }
}
