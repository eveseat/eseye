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

echo 'Reading esi.json ..' . PHP_EOL;
$esi = json_decode(file_get_contents('esi.json'), true);

echo 'ESI Version: ' . $esi['info']['version'] . PHP_EOL;

$scope_map = [
    'get'    => [],
    'post'   => [],
    'put'    => [],
    'delete' => [],
    'patch'  => [],
];

echo 'Mapping Scopes to endpoints and methods ..' . PHP_EOL;
foreach ($esi['paths'] as $path => $description) {

    foreach ($description as $method => $data) {

        if (isset($data['security']))
            $scope = $data['security'][0]['evesso'][0];
        else
            $scope = 'public';

        // Update the scope map!
        $scope_map[$method][$path] = $scope;
        echo $method . ' | ' . $path . ' | ' . $scope . PHP_EOL;
    }
}

// Output the scopes in a way that we can
// copy/pasta in code!
echo 'Var Export of the scope map:' . PHP_EOL;
echo PHP_EOL;
var_export($scope_map);
echo PHP_EOL;
