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

use Seat\Eseye\Containers\EsiResponse;
use Seat\Eseye\Exceptions\RequestFailedException;

class RequestFailedExceptionTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var RequestFailedException
     */
    protected $exception;

    public function setUp()
    {

        $this->exception = new RequestFailedException(new Exception('Foo'), new EsiResponse(
            json_encode(['error' => 'test']),
            [],
            'now',
            500
        ));
    }

    public function testRequestFailedGetsErrors()
    {

        $error = $this->exception->getError();

        $this->assertEquals('test', $error);
    }

    public function testRequestFailedGetsEsiResponse()
    {

        $response = $this->exception->getEsiResponse();

        $this->assertInstanceOf(EsiResponse::class, $response);
    }

    public function testRequestFailedGetsOriginalException()
    {

        $response = $this->exception->getOriginalException();

        $this->assertInstanceOf(Exception::class, $response);
    }

}
