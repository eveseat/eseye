<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015, 2016, 2017, 2018, 2019  Leon Jacobs
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

namespace Seat\Eseye\Traits;

use Seat\Eseye\Exceptions\InvalidContainerDataException;

/**
 * Class ConstructsContainers.
 * @package Seat\Eseye\Traits
 */
trait ConstructsContainers
{
    /**
     * ConstructsContainers constructor.
     *
     * This constructor is used in Containers to allow setting
     * data when a new instance is created. It will validate
     * the incoming array to ensure that only the keys in
     * the data property of the container is set.
     *
     * @param array|null $data
     *
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     */
    public function __construct(array $data = null)
    {

        if (! is_null($data)) {

            foreach ($data as $key => $value) {

                if (! array_key_exists($key, $this->data))
                    throw new InvalidContainerDataException(
                        'Key ' . $key . ' is not valid for this container'
                    );

                $this->$key = $value;
            }
        }
    }
}
