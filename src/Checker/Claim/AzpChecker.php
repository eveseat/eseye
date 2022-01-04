<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015 to 2022 Leon Jacobs
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

namespace Seat\Eseye\Checker\Claim;

use Jose\Component\Checker\ClaimChecker;
use Jose\Component\Checker\InvalidClaimException;

/**
 * Class AzpChecker.
 *
 * @package Seat\Web\Extentions\Socialite\EveOnline\Checker\Claim
 */
class AzpChecker implements ClaimChecker
{
    private const NAME = 'azp';

    /**
     * @var string
     */
    private $client_id;

    /**
     * AzpChecker constructor.
     *
     * @param  string  $client_id
     */
    public function __construct(string $client_id)
    {
        $this->client_id = $client_id;
    }

    /**
     * {@inheritdoc}
     */
    public function checkClaim($value): void
    {
        if (! is_string($value))
            throw new InvalidClaimException('"azp" must be a string.', self::NAME, $value);

        if ($value !== $this->client_id)
            throw new InvalidClaimException('"azp" must match the originating application.', self::NAME, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function supportedClaim(): string
    {
        return self::NAME;
    }
}
