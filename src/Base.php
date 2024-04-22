<?php

declare(strict_types=1);

namespace Camoo\Cache;

use Camoo\Cache\Factory\CacheSystemFactory;
use Camoo\Cache\Interfaces\CacheSystemFactoryInterface;
use Camoo\Cache\InvalidArgumentException as SimpleCacheInvalidArgumentException;

/**
 * Abstract base class for a caching system, providing common utility methods.
 *
 * @author CamooSarl
 */
abstract class Base
{
    private const INVALID_MESSAGE = 'The key provided is not valid.';

    /**
     * Loads the factory for creating cache systems.
     *
     * @return CacheSystemFactoryInterface Returns an instance of CacheSystemFactory.
     */
    protected function loadFactory(): CacheSystemFactoryInterface
    {
        return CacheSystemFactory::create();
    }

    /**
     * Validates the cache key to ensure it is a non-empty string.
     *
     * @param string $key The key to validate.
     *
     * @throws SimpleCacheInvalidArgumentException If the key is not valid.
     */
    protected function validateKey(string $key): void
    {
        if (trim($key) === '') {
            throw new SimpleCacheInvalidArgumentException(self::INVALID_MESSAGE);
        }
    }
}
