<?php

namespace Seat\Eseye\Cache;

use DateInterval;

trait CommonOperations
{
    /**
     * @param  iterable  $values
     * @param  \DateInterval|int|null  $ttl
     *
     * @return bool
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (! $this->set($key, $value, $ttl))
                return false;
        }

        return true;
    }

    /**
     * @param  iterable  $keys
     * @param  mixed|null  $default
     *
     * @return iterable
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $entries = [];

        foreach ($keys as $key) {
            $entries[$key] = $this->get($key, $default);
        }

        return $entries;
    }

    /**
     * @param  iterable  $keys
     *
     * @return bool
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            if (! $this->delete($key))
                return false;
        }

        return true;
    }
}