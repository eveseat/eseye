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

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\EseyeFetcher;
use Seat\Eseye\Exceptions\InvalidAuthenticationException;
use Seat\Eseye\Exceptions\RequestFailedException;
use Seat\Eseye\Fetchers\GuzzleFetcher;
use Seat\Eseye\Log\NullLogger;

class GuzzleFetcherTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var GuzzleFetcher
     */
    protected $fetcher;

    public function setUp()
    {

        // Remove logging
        $configuration = Configuration::getInstance();
        $configuration->logger = NullLogger::class;

        $this->fetcher = new GuzzleFetcher;
    }

    public function testGuzzleFetcherInstantiation()
    {

        $this->assertInstanceOf(GuzzleFetcher::class, $this->fetcher);
    }

    public function testGuzzleGetsClientIfNoneSet()
    {

        $fetcher = new GuzzleFetcher;
        $client = $fetcher->getClient();

        $this->assertInstanceOf(Client::class, $client);
    }

    public function testGuzzleFetcherStripRefreshTokenFromUrl()
    {

        $url = 'https://esi.url/oauth?type=refresh_token&refresh_token=foo';
        $stripped = $this->fetcher->stripRefreshTokenValue($url);

        $this->assertEquals('https://esi.url/oauth?type=refresh_token', $stripped);
    }

    public function testGuzzleFetcherStripRefreshTokenFromUrlWithoutRefreshToken()
    {

        $url = 'https://esi.url/type=refresh_token';
        $stripped = $this->fetcher->stripRefreshTokenValue($url);

        $this->assertEquals('https://esi.url/type=refresh_token', $stripped);
    }

    public function testGuzzleFetcherStripRefreshTokenNoTokenMention()
    {

        $url = 'https://esi.url/foo=bar';
        $stripped = $this->fetcher->stripRefreshTokenValue($url);

        $this->assertEquals($url, $stripped);
    }

    public function testGuzzleFetcherMakeEsiResponseContainer()
    {

        $response = json_encode(['response' => 'ok']);

        $container = $this->fetcher->makeEsiResponse($response, [], 'now', 200);

        $this->assertInstanceOf(EsiResponse::class, $container);
    }

    public function testGuzzleFetcherGetAuthenticationWhenNoneSet()
    {

        $authentication = $this->fetcher->getAuthentication();

        $this->assertNull($authentication);
    }

    public function testGuzzleFetcherGetAuthenticationWhenSettingAuthentication()
    {

        $fetcher = new GuzzleFetcher(new EsiAuthentication([
            'client_id' => 'foo',
        ]));

        $this->assertInstanceOf(EsiAuthentication::class, $fetcher->getAuthentication());
    }

    public function testGuzzleSetsAuthentication()
    {

        $this->fetcher->setAuthentication(new EsiAuthentication([
            'client_id'     => 'foo',
            'secret'        => 'bar',
            'access_token'  => '_',
            'refresh_token' => 'baz',
            'token_expires' => '1970-01-01 00:00:00',
            'scopes'        => ['public'],
        ]));

        $this->assertInstanceOf(EsiAuthentication::class, $this->fetcher->getAuthentication());
    }

    public function testGuzzleFailsSettingInvalidAuthentication()
    {

        $this->expectException(InvalidAuthenticationException::class);

        $this->fetcher->setAuthentication(new EsiAuthentication([
            'client_id' => null,
        ]));
    }

    public function testGuzzleShouldFailGettingTokenWithoutAuthentication()
    {

        $this->expectException(InvalidAuthenticationException::class);

        $get_token = self::getMethod('getToken');
        $get_token->invokeArgs(new GuzzleFetcher, []);
    }

    /**
     * Helper method to set private methods public.
     *
     * @param $name
     *
     * @return \ReflectionMethod
     */
    protected static function getMethod($name)
    {

        $class = new ReflectionClass('Seat\Eseye\Fetchers\GuzzleFetcher');
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    public function testGuzzleFetcherGetPublicScopeWithoutAuthentication()
    {

        $scopes = $this->fetcher->getAuthenticationScopes();

        $this->assertEquals(1, count($scopes));
    }

    public function testGuzzleCallingWithoutAuthentication()
    {

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode(['foo' => 'var'])),
        ]);

        // Update the fetchers client
        $this->fetcher->setClient(new Client([
            'handler' => HandlerStack::create($mock),
        ]));

        $response = $this->fetcher->call('get', '/foo', ['foo' => 'bar']);

        $this->assertInstanceOf(EsiResponse::class, $response);
    }

    public function testGuzzleCallingWithAuthentication()
    {

        $mock = new MockHandler([
            // RefreshToken response
            new Response(200, ['X-Foo' => 'Bar'], json_encode([
                'access_token' => 'foo', 'expires_in' => 1200, 'refresh_token' => 'bar',
            ])),
            new Response(200, ['X-Foo' => 'Bar'], json_encode(['foo' => 'var'])),
        ]);

        // Update the fetchers client
        $this->fetcher->setClient(new Client([
            'handler' => HandlerStack::create($mock),
        ]));

        // Update the fetchers authentication
        $this->fetcher->setAuthentication(new EsiAuthentication([
            'client_id'     => 'foo',
            'secret'        => 'bar',
            'access_token'  => '_',
            'refresh_token' => 'baz',
            'token_expires' => '1970-01-01 00:00:00',
            'scopes'        => ['public'],
        ]));

        $response = $this->fetcher->call('get', '/foo', ['foo' => 'bar']);

        $this->assertInstanceOf(EsiResponse::class, $response);
    }

    public function testGuzzleCallingCatchesRequestAuthenticationFailure()
    {

        $this->expectException(RequestFailedException::class);

        $mock = new MockHandler([
            new Response(401),
        ]);

        // Update the fetchers client
        $this->fetcher->setClient(new Client([
            'handler' => HandlerStack::create($mock),
        ]));

        $this->fetcher->call('get', '/foo', ['foo' => 'bar']);
    }

    public function testGuzzleFetcherMakesHttpRequest()
    {

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode(['foo' => 'var'])),
        ]);

        // Update the fetchers client
        $this->fetcher->setClient(new Client([
            'handler' => HandlerStack::create($mock),
        ]));

        $response = $this->fetcher->httpRequest('get', '/foo');

        $this->assertInstanceOf(EsiResponse::class, $response);

    }

    public function testGuzzleConstructsWithClientAndGetsAuthenticationScopes()
    {

        $mock = new MockHandler([
            // RefreshToken response
            new Response(200, ['X-Foo' => 'Bar'], json_encode([
                'access_token' => 'foo', 'expires_in' => 1200, 'refresh_token' => 'bar',
            ])),
            new Response(200, ['X-Foo' => 'Bar'], json_encode([
                'Scopes' => 'foo bar baz',
            ])),
        ]);

        // Update the fetchers client
        $client = new Client([
            'handler' => HandlerStack::create($mock),
        ]);

        // Update the fetchers authentication
        $authentication = new EsiAuthentication([
            'client_id'     => 'foo',
            'secret'        => 'bar',
            'access_token'  => '_',
            'refresh_token' => 'baz',
            'token_expires' => '1970-01-01 00:00:00',
        ]);

        $fetcher = new GuzzleFetcher($authentication);
        $fetcher->setClient($client);

        $scopes = $fetcher->getAuthenticationScopes();

        $this->assertEquals(['foo', 'bar', 'baz', 'public'], $scopes);
    }
}
