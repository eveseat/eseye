<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015 to present Leon Jacobs
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

include 'vendor/autoload.php';

use GuzzleHttp\Client;
use Seat\Eseye\Cache\NullCache;
use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Eseye;

// Disable all caching by setting the NullCache as the
// preferred cache handler. By default, Eseye will use the
// FileCache.
$configuration = Configuration::getInstance();
$configuration->cache = NullCache::class;
$configuration->http_client = Client::class;

$character_id = 90795931;

// Prepare an authentication container for ESI
$authentication = new EsiAuthentication([
    'client_id'     => 'b9e0525d7c1f4f85b90de34693f27340',
    'secret'        => 'Mx5YvKVL12Ds44JuwUfXLYeFE2XfOZmIHJ00CwZG',
    'access_token'  => 'eyJhbGciOiJSUzI1NiIsImtpZCI6IkpXVC1TaWduYXR1cmUtS2V5IiwidHlwIjoiSldUIn0.eyJzY3AiOlsiZXNpLWNsb25lcy5yZWFkX2Nsb25lcy52MSIsImVzaS1sb2NhdGlvbi5yZWFkX2xvY2F0aW9uLnYxIl0sImp0aSI6IjIzY2Y1MzdlLWEwNjItNDg3OC1iMDZkLTVhNDdkNWQyOGI4ZCIsImtpZCI6IkpXVC1TaWduYXR1cmUtS2V5Iiwic3ViIjoiQ0hBUkFDVEVSOkVWRTo5MDc5NTkzMSIsImF6cCI6ImI5ZTA1MjVkN2MxZjRmODViOTBkZTM0NjkzZjI3MzQwIiwidGVuYW50IjoidHJhbnF1aWxpdHkiLCJ0aWVyIjoibGl2ZSIsInJlZ2lvbiI6IndvcmxkIiwiYXVkIjoiRVZFIE9ubGluZSIsIm5hbWUiOiJXYXJsb2YgVHV0c2ltbyIsIm93bmVyIjoic3ZuU2pWYTF1R1l5cC9aTDNtZmtJd2tKWXpRPSIsImV4cCI6MTY4NDQzMTAzOSwiaWF0IjoxNjg0NDI5ODM5LCJpc3MiOiJsb2dpbi5ldmVvbmxpbmUuY29tIn0.iIZFLIhG9DStb206cJ4NFQOe7AqrjXWdoFKIwpRvELnCVT9ex03KXXXHwdWLlSHTt4J63PVfHStnGhbwhPolg4wgxYCxaGRVxQPbJXLYeQ8e6olebvB2Dqs7yZOInfHl71xVKx4EVSOM8nNfb_I580jENoowJbZ9XgXTw-ezFejhEzYjOyY-ZEC700mkORPNCURbyGqCLQ-qfMz1Ov7ZB9FXRijmnl-KHcFPBIo4zz_DGIME1qFcXfsGgx2JNRB0-ZGC0g2BTqa_td-Bz0i7lGhnNq9-Tv9HQvgAabkTrUuc0JWAELwJMx0vwufyg13w1tKF9snDfQmt-fSF7qhdWQ',
    'refresh_token' => '1nKbHcl9qUSx6dQ+ubLLLQ==',
]);

// Instantiate a new ESI instance.
$esi = new Eseye($authentication);

// Get character information. This is a public call to the EVE
// Swagger Interface
$character_info = $esi->invoke('get', '/characters/{character_id}/', [
    'character_id' => $character_id,
]);

// Get the location information for a character. This is an authenticated
// call to the EVE Swagger Interface.
$location = $esi->invoke('get', '/characters/{character_id}/location/', [
    'character_id' => $character_id,
]);

$clones = $esi->invoke('get', '/characters/{character_id}/clones/', [
    'character_id' => $character_id,
]);

// Print some information from the calls we have made.
echo 'Character Name is:        ' . $character_info->name . PHP_EOL;
echo 'Character was born:       ' . carbon($character_info->birthday)
        ->diffForHumans() . PHP_EOL;    // The 'carbon' helper is included in the package.
echo PHP_EOL;
echo 'Home Solar System ID is:  ' . $location->solar_system_id . PHP_EOL;
if (property_exists($location, 'station_id'))
    echo 'Home Station ID is:       ' . $location->station_id . PHP_EOL;
if (property_exists($location, 'structure_id'))
    echo 'Home Structure ID is:     ' . $location->structure_id . PHP_EOL;
echo PHP_EOL;
echo 'You have the following clones: ' . PHP_EOL;
foreach ($clones->jump_clones as $jump_clone) {

    echo 'Clone at a ' . $jump_clone->location_type .
        ' with ' . count($jump_clone->implants) . ' implants' . PHP_EOL;
}

if ($esi->getAuthentication()->refresh_token != $authentication->refresh_token ||
    $esi->getAuthentication()->access_token != $authentication->access_token) {
    echo 'Authorization has been updated' . PHP_EOL;
    echo PHP_EOL;
    echo 'New access token: ' . $esi->getAuthentication()->access_token . PHP_EOL;
    echo 'New refresh token: ' . $esi->getAuthentication()->refresh_token . PHP_EOL;
    echo 'Expires: ' . carbon($esi->getAuthentication()->token_expires)->diffForHumans();
}
