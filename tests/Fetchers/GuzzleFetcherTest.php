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
use Jose\Component\Core\JWK;
use Jose\Easy\Build;
use PHPUnit\Framework\TestCase;
use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\Exceptions\InvalidAuthenticationException;
use Seat\Eseye\Exceptions\RequestFailedException;
use Seat\Eseye\Fetchers\GuzzleFetcher;
use Seat\Eseye\Log\NullLogger;

class GuzzleFetcherTest extends TestCase
{

    /**
     * @var GuzzleFetcher
     */
    protected $fetcher;

    public function setUp(): void
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

        // init a JWK set
        $jwk = $this->getJwkSet();

        // generate a JWS Token mocking standard CCP format
        $jws = $this->getJwsToken($jwk);

        $mock = new MockHandler([
            // RefreshToken response
            new Response(200, ['X-Foo' => 'Bar'], json_encode([
                'access_token'  => $jws,
                'expires_in'    => 1200,
                'token_type'    => 'Bearer',
                'refresh_token' => 'bar',
            ])),
            // JWKS endpoint response
            new Response(200, [], json_encode([
                'jwks_uri' => 'https://login.eveonline.com/oauth/jwks',
            ])),
            // JWK Sets response
            new Response(200, [], json_encode([
                'keys' => [
                    $jwk->jsonSerialize(),
                ],
                'SkipUnresolvedJsonWebKeys' => true,
            ])),
            // ESI response
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

        // init a JWK Set
        $jwk = $this->getJwkSet();

        // init a JWS Token
        $jws = $this->getJwsToken($jwk);

        $mock = new MockHandler([
            // JWK Endpoint
            new Response(200, [], json_encode([
                'jwks_uri' => 'https://login.eveonline.com/oauth/jwks',
            ])),
            // JWK Sets response
            new Response(200, [], json_encode([
                'keys' => [
                    $jwk->jsonSerialize(),
                ],
                'SkipUnresolvedJsonWebKeys' => true,
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
            'access_token'  => $jws,
            'refresh_token' => 'baz',
            'token_expires' => '1970-01-01 00:00:00',
        ]);

        $fetcher = new GuzzleFetcher($authentication);
        $fetcher->setClient($client);

        $scopes = $fetcher->getAuthenticationScopes();

        $this->assertEquals(['foo', 'bar', 'baz', 'public'], $scopes);
    }

    /**
     * @return \Jose\Component\Core\JWK
     */
    private function getJwkSet()
    {
        return new JWK([
            "p"   => "1UQV33bi2J-WJ9529sOTuXiAGCh_lcUAgRHayLbBSElC9O_kA8g2ipC0Qu58tpKdKjq2dD7_SfbESqEI0AJD7oMfP1i-Ispn31vjIb7fmnlddF2qflc9SEkWkrZPCntusTzIraxBDUwIlmdOdAI24xHHpGe-DISE4R1LYrQS0m0",
            "kty" => "RSA",
            "q"   => "yaEesJOxfHkeYvlo8f1NVCrCyxfzDl3_-_qDm-bpdUDjemsvolYD6AEb0GGiyjFdMJg29iCke_8nzYIuwDf2QzFS96aU0IpxLwyNsXBdOr7K53WmDj7LU4xFfR-gaOQEp-o2KZ7-1EqpPRgeI12wVpfR3Mi4TZuXlgmeyYpt_BU",
            "d"   => "iZo3pjr-cegcZN2lk3I3qL8By-8bSO4DaZdih6BnHB-VJbhhmSky64wP34wpKe_G486C1o9IsVZ6zuhXrOJdEOIike3d0IKg6jNY24RsV-AX5hYn44Us8ePHQnhqtxf42GujloroctLkAJAlpnYg6-rWW7kAoCjrNxVIaJ4AOabIcpIQwNwqHNjWifik6WttpC_-u-4HmTLG6f-NrVqrSJkBTReRCjsSYz1GH_snBGx2SFS5XbEfvWe0g-Asu8kZPzYL7y5ahMZ3AT4qOjNA-UFkIzYW0BQjwMwTzRq_30bTDZXBLH8YbDCD6HwBhVThmX4k6AjWr4yG7-uJfvTAgQ",
            "e"   => "AQAB",
            "use" => "sig",
            "kid" => "JWK Signature Mock",
            "qi"  => "baoXJXUnqalu6CMibn1aILUhfDbjW_lVVk-b9ZAK7ZVi8vEKDpvFWTeB-R-kZMaQcYZKSOZshfXSOd8p_hpCY7nL5XuQPndR2jg23vnsddppT8dwOsiOPX1gQufwrtTQX9oibcDX1P5z942esrbHW5ttEkgM4i8Xo5G7tkKed8k",
            "dp"  => "d2kK8jdn5rDca3Blnd9-HFA7MMukPGC02o_7t3yUlnvm0KxtOCznVQiW1g8gpz1KYLXFKSuI14oi-EJYY9eQ38BtQ5PVyjcYl_ikIWX1X1HrINe9OcZxGsNJr1YCxbS9EuIc3xlexyo2eLhZNh1zTArNhOFNiUa9_Cnh5t861rU",
            "alg" => "RS256",
            "dq"  => "rUXJGfXSkSWE94leppcH3UziGaZ7Od2OHv0qHNBT0G_zDUEPrnI86SQKwwkk3J2PeDNXCC0FLYoYqoM1qfptp1C7_BcrzAstOUGQguwNMm7D8CUqjxNnqGTjUqPbNki9t4-O_DWmyMlgpyASxlG9OK0_rHzR5d_QZR_fVVOhMQ",
            "n"  => "p_iuj0pLDqQsdtXl6cJ8Gqhtm8F5dUSbmNPYoNbB_uM0oQrBBxvKPH7GzDduFMtS6LfloH3hGryTum-lxU1yQ4PjaN3IEdrGpqS6_OWtqZ6mRWrDDNdgWmkFtq5kPwfR2EXdcygWREJ7w1376WWx9l3tVu6zygfCghTTUhVT65fjmnNUR6zWJn15pxTjnQ-zphKlgvWnCDwJEW9UFXK025ztMQFn8rkTxJV9O3Qu3QS-VRjVicPhV7oOMs2YhiqUAmnzu285nKTaG6N_83NIZ5W2N06JfMt6epvTiC-st2joQp4FCsiVPEEQ6wjZJTA7cpdmIoc05X7gMKdipxeO8Q"
        ]);
    }

    /**
     * @param \Jose\Component\Core\JWK $jwk
     * @return string
     */
    private function getJwsToken(JWK $jwk): string
    {
        $time = time();

        return Build::jws()
            ->exp($time + 1200)
            ->alg('RS256')
            ->payload([
                'scp' => [
                    "foo",
                    "bar",
                    "baz",
                    "public",
                ],
                "jti" => "7f64ea9f-ee6e-4c4a-9486-d668e8c79f25",
                "kid" => "JWT-Signature-Key",
                "sub" => "CHARACTER:EVE:90795931",
                "azp" => "foo",
                "name" => "Warlof Tutsimo",
                "owner" => "svnSjVa1uGYyp/ZL3mfkIwkJYzQ=",
                "exp"   => $time + 3600,
                "iss" => "https://login.eveonline.com",
            ])
            ->header('typ', 'JWT')
            ->header('kid', 'JWT-Signature-Key')
            ->sign($jwk);
    }
}
