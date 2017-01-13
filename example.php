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

include 'vendor/autoload.php';

use Seat\Eseye\{
    Containers\EsiAuthentication, Eseye
};

// Prepare an authentication container for ESI
$authentication = new EsiAuthentication([
    'client_id'     => 'SSO_CLIENT_ID',
    'secret'        => 'SSO_SECRET',
    'access_token'  => 'CHARACTER_ACCESS_TOKEN',
    'refresh_token' => 'CHARACTER_REFRESH_TOKEN',
]);

// Instantiate a new ESI instance.
$esi = new Eseye($authentication);

// Get the location information for a character.
$location = $esi->fetch('get', '/characters/{character_id}/location/', [
    'character_id' => 1477919642,
]);

var_dump($location);