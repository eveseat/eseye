<?php

namespace Seat\Eseye\Cache;

use InvalidArgumentException;
use Seat\Eseye\Containers\EsiResponse;

trait ValidateCacheEntry
{
    /**
     * @param  mixed  $value
     * @return void
     */
    public function validateCacheValue(mixed $value): void
    {
        if (! $value instanceof EsiResponse)
            throw new InvalidArgumentException('An EsiResponse object was expected as cache value.');
    }

    /**
     * @param  string  $key
     * @param  string|null  $path
     * @param  string|null  $query
     *
     * @return void
     */
    public function validateCacheKey(string $key, ?string &$path, ?string &$query): void
    {
        $path = parse_url($key, PHP_URL_PATH);
        $query = parse_url($key, PHP_URL_QUERY);

        if ($path === false)
            throw new InvalidArgumentException('A valid URI was expected as cache key.');

        if ($query === false)
            throw new InvalidArgumentException('A valid URI was expected as cache key.');

        if ($path === null)
            $path = '/';

        if ($query === null)
            $query = '';
    }
}
