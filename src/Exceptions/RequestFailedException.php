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

namespace Seat\Eseye\Exceptions;

use Exception;
use Seat\Eseye\Containers\EsiResponse;

/**
 * Class RequestFailedException.
 * @package Seat\Eseye\Exceptions
 */
class RequestFailedException extends Exception
{

    /**
     * @var \Seat\Eseye\Containers\EsiResponse
     */
    private $esi_response;

    /**
     * @var \Exception
     */
    private $original_exception;

    /**
     * RequestFailedException constructor.
     *
     * @param \Exception                         $exception
     * @param \Seat\Eseye\Containers\EsiResponse $esi_response
     */
    public function __construct(Exception $exception, EsiResponse $esi_response)
    {

        $this->esi_response = $esi_response;
        $this->original_exception = $exception;

        // Finish constructing the exception
        parent::__construct(
            $this->getError(),
            $this->getEsiResponse()->getErrorCode(),
            $exception->getPrevious()
        );
    }

    /**
     * @return null|string
     */
    public function getError()
    {

        return $this->getEsiResponse()->error();
    }

    /**
     * @return \Seat\Eseye\Containers\EsiResponse
     */
    public function getEsiResponse(): EsiResponse
    {

        return $this->esi_response;
    }

    /**
     * @return \Exception
     */
    public function getOriginalException(): Exception
    {

        return $this->original_exception;
    }
}
