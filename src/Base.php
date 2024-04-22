<?php

declare(strict_types=1);

namespace Camoo\Cache;

use Camoo\Cache\Exception\AppCacheException;
use Camoo\Cache\Factory\CacheSystemFactory;
use Camoo\Cache\Interfaces\CacheSystemFactoryInterface;
use Camoo\Cache\InvalidArgumentException as SimpleCacheInvalidArgumentException;
use DateInterval;
use DateTime;
use Exception;
use Throwable;

/**
 * Abstract base class for a caching system, providing common utility methods.
 *
 * @author CamooSarl
 */
abstract class Base
{
    private const INVALID_MESSAGE = 'The key provided is not valid.';

    private const INVALID_TTL_MESSAGE = 'TTL is not a legal value';

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

    /**
     * Parses the TTL value into a DateInterval object if necessary.
     *
     * @param int|DateInterval|string|null $ttl The TTL value to parse.
     *
     * @throws SimpleCacheInvalidArgumentException If the TTL value is not valid.
     * @throws Exception
     *
     * @return DateInterval|null The parsed DateInterval or null if no TTL was provided.
     */
    protected function parseTtl(int|DateInterval|string|null $ttl): ?DateInterval
    {
        if ($ttl === null) {
            return null;
        }

        if (is_string($ttl)) {
            if (!preg_match('/^\+/', $ttl)) {
                throw new SimpleCacheInvalidArgumentException(self::INVALID_TTL_MESSAGE . ': Must start with +');
            }

            try {
                $now = new DateTime('now');
                $modifiedTime = $now->modify($ttl);
                if ($modifiedTime === false) {
                    throw new SimpleCacheInvalidArgumentException('Failed to modify DateTime with string ' . $ttl);
                }

                $sec = $modifiedTime->getTimestamp() - time();
                if ($sec < 0) {
                    throw new AppCacheException('Calculated negative TTL from DateTime modification.');
                }

                return new DateInterval(sprintf('PT%dS', $sec));
            } catch (Throwable $exception) {
                throw new SimpleCacheInvalidArgumentException(self::INVALID_TTL_MESSAGE . ': ' .
                    $exception->getMessage(), 0, $exception);
            }
        }

        return is_int($ttl) ? new DateInterval(sprintf('PT%dS', $ttl)) : $ttl;
    }
}
