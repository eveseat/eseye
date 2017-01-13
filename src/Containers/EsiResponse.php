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

use ArrayAccess;
use Carbon\Carbon;
use Iterator;


/**
 * Class EsiResponse
 * @package Seat\Eseye\Containers
 */
class EsiResponse implements ArrayAccess, Iterator
{
    /**
     * @var
     */
    private $position;

    /**
     * @var array
     */
    protected $data;

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
     * @param array  $data
     * @param string $expires
     * @param int    $response_code
     */
    public function __construct(
        array $data, string $expires, int $response_code)
    {

        $this->data = $data;

        // Ensure that the value for 'expires' is longer than
        // 2 character. The shortest expected value is 'now'
        $this->expires_at = strlen($expires) > 2 ? $expires : 'now';
        $this->response_code = $response_code;

        $this->position = 0;

        // If there is an error, set that
        if (array_key_exists('error', $data))
            $this->error_message = $data['error'];
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {

        return array_key_exists($offset, $this->data);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {

        return $this->data[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {

        $this->data[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {

        unset($this->data[$offset]);
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function __get($key)
    {

        return $this[$key];
    }

    /**
     * @param $key
     * @param $val
     */
    public function __set($key, $val)
    {

        $this[$key] = $val;
    }

    /**
     * @return mixed
     */
    public function current()
    {

        return $this->data[$this->position];
    }

    /**
     * @return mixed
     */
    public function next()
    {

        return ++$this->position;
    }

    /**
     *
     */
    public function key()
    {

        return $this->position;
    }

    /**
     * @return bool
     */
    public function valid()
    {

        return isset($this->data[$this->position]);
    }

    /**
     *
     */
    public function rewind()
    {

        $this->position = 0;
    }

    /**
     * @return \Carbon\Carbon
     */
    public function expires(): Carbon
    {

        return carbon($this->expires_at);
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
     * @return string
     */
    public function error(): string
    {

        return $this->error_message;
    }
}