<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015, 2016, 2017, 2018  Leon Jacobs
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

use Seat\Eseye\Cache\NullCache;
use Seat\Eseye\Configuration;
use Seat\Eseye\Containers\EsiAuthentication;
use Seat\Eseye\Eseye;

// Disable all caching by setting the NullCache as the
// preferred cache handler. By default, Eseye will use the
// FileCache.
$configuration = Configuration::getInstance();
$configuration->cache = NullCache::class;

// Prepare an authentication container for ESI
$authentication = new EsiAuthentication([
    'client_id'     => 'SSO_CLIENT_ID',
    'secret'        => 'SSO_SECRET',
    'refresh_token' => 'CHARACTER_REFRESH_TOKEN',
]);

// Instantiate a new ESI instance.
$esi = new Eseye($authentication);

// Get character information. This is a public call to the EVE
// Swagger Interface
$character_info = $esi->invoke('get', '/characters/{character_id}/', [
    'character_id' => 1477919642,
]);

// Get the location information for a character. This is an authenticated
// call to the EVE Swagger Interface.
$location = $esi->invoke('get', '/characters/{character_id}/location/', [
    'character_id' => 1477919642,
]);

$clones = $esi->invoke('get', '/characters/{character_id}/clones/', [
    'character_id' => 1477919642,
]);

// Print some information from the calls we have made.
echo 'Character Name is:        ' . $character_info->name . PHP_EOL;
echo 'Character was born:       ' . carbon($character_info->birthday)
        ->diffForHumans() . PHP_EOL;    // The 'carbon' helper is included in the package.
echo 'Home Solar System ID is:  ' . $location->solar_system_id . PHP_EOL;
echo 'Home Station ID is:       ' . $location->station_id . PHP_EOL;

echo 'You have the following clones: ' . PHP_EOL;
foreach ($clones->jump_clones as $jump_clone) {

    echo 'Clone at a ' . $jump_clone->location_type .
        ' with ' . count($jump_clone->implants) . ' implants' . PHP_EOL;
}
