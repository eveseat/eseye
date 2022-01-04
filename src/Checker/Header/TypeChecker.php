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

namespace Seat\Eseye\Checker\Header;

use Jose\Component\Checker\HeaderChecker;
use Jose\Component\Checker\InvalidHeaderException;

/**
 * Class TypeChecker.
 *
 * @package Seat\Web\Extentions\Socialite\EveOnline\Checker
 */
final class TypeChecker implements HeaderChecker
{
    private const HEADER_NAME = 'typ';

    /**
     * @var bool
     */
    private $protected_header = true;

    /**
     * @var string[]
     */
    private $supported_types;

    /**
     * TypeChecker constructor.
     *
     * @param  string[]  $supported_types
     * @param  bool  $protected_header
     */
    public function __construct(array $supported_types, bool $protected_header = true)
    {
        $this->supported_types = $supported_types;
        $this->protected_header = $protected_header;
    }

    /**
     * {@inheritdoc}
     */
    public function checkHeader($value): void
    {
        if (! is_string($value))
            throw new InvalidHeaderException('"typ" must be a string.', self::HEADER_NAME, $value);

        if (! in_array($value, $this->supported_types, true))
            throw new InvalidHeaderException('Unsupported type.', self::HEADER_NAME, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function supportedHeader(): string
    {
        return self::HEADER_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function protectedHeaderOnly(): bool
    {
        return $this->protected_header;
    }
}
