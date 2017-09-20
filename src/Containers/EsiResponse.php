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

namespace Seat\Eseye\Containers;

use ArrayObject;
use Carbon\Carbon;
use stdClass;

/**
 * Class EsiResponse.
 * @package Seat\Eseye\Containers
 */
class EsiResponse extends ArrayObject
{
    /**
     * @var array
     */
    protected $expires_at;

    /**
     * @var string
     */
    protected $response_code;

    /**
     * @var mixed
     */
    protected $error_message;

    /**
     * EsiResponse constructor.
     *
     * @param stdClass $data
     * @param string   $expires
     * @param int      $response_code
     */
    public function __construct(
        stdClass $data, string $expires, int $response_code)
    {

        // Ensure that the value for 'expires' is longer than
        // 2 character. The shortest expected value is 'now'
        $this->expires_at = strlen($expires) > 2 ? $expires : 'now';
        $this->response_code = $response_code;

        // If there is an error, set that
        if (property_exists($data, 'error'))
            $this->error_message = $data->error;

        // If there is an error description, set that.
        if (property_exists($data, 'error_description'))
            $this->error_message .= ': ' . $data->error_description;

        // Run the parent constructor
        parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Determine if this containers data should be considered
     * expired.
     *
     * Expiry is calculated by taking the expiry time and comparing
     * that to the local time. Before comparison though, the local
     * time is converted to the timezone in which the expiry time
     * is recorded. The resultant local time is then checked to
     * ensure that the expiry is not less than local time.
     *
     * @return bool
     */
    public function expired(): bool
    {

        if ($this->expires()->lte(
            carbon()->now($this->expires()->timezoneName))
        )
            return true;

        return false;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function expires(): Carbon
    {

        return carbon($this->expires_at);
    }

    /**
     * @return null|string
     */
    public function error()
    {

        return $this->error_message;
    }

    /**
     * @return int
     */
    public function getErrorCode(): int
    {

        return $this->response_code;
    }
}
