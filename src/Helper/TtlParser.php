<?php

declare(strict_types=1);

namespace Camoo\Cache\Helper;

use Camoo\Cache\Exception\AppCacheException;
use DateInterval;
use DateTimeImmutable;
use Exception;
use Throwable;

/**
 * Class TtlParser
 *
 * Provides functionality to parse TTL values into DateInterval objects.
 */
final class TtlParser
{
    private const INVALID_TTL_MESSAGE = 'Invalid TTL value';

    /**
     * Parses the TTL value into a DateInterval object if necessary.
     *
     * @param int|DateInterval|string|null $ttl The TTL value to parse.
     *
     * @throws Exception If the TTL value is not valid or cannot be parsed.
     *
     * @return DateInterval|null The parsed DateInterval or null if no TTL was provided.
     */
    public function toDateInterval(int|DateInterval|string|null $ttl): ?DateInterval
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof DateInterval) {
            if ($ttl->invert === 1) {
                throw new AppCacheException(self::INVALID_TTL_MESSAGE . ': must not be negative');
            }

            return $ttl;
        }

        if (is_string($ttl)) {
            if (!preg_match('/^\+/', $ttl)) {
                throw new AppCacheException(self::INVALID_TTL_MESSAGE . ': Must start with +');
            }

            try {
                $now = new DateTimeImmutable('now');
                $modifiedTime = $now->modify($ttl);
                if ($modifiedTime === false) {
                    throw new AppCacheException('Failed to modify DateTime with string: ' . $ttl);
                }

                $seconds = $modifiedTime->getTimestamp() - $now->getTimestamp();
                if ($seconds < 0) {
                    throw new AppCacheException('Calculated negative TTL from DateTime modification.');
                }

                $ttl = new DateInterval(sprintf('PT%dS', $seconds));
            } catch (Throwable $exception) {
                throw new AppCacheException(self::INVALID_TTL_MESSAGE . ': ' . $exception->getMessage(), 0, $exception);
            }
        }

        if (is_int($ttl)) {
            if ($ttl < 0) {
                throw new AppCacheException(self::INVALID_TTL_MESSAGE . ': must not be negative');
            }

            return new DateInterval(sprintf('PT%dS', $ttl));
        }

        if ($ttl->invert === 1) {
            throw new AppCacheException(self::INVALID_TTL_MESSAGE . ': must not be negative');
        }

        return $ttl;
    }

    /** @throws Exception */
    public function toSeconds(int|DateInterval|string|null $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if (is_int($ttl)) {
            return $ttl;
        }

        $interval = $this->toDateInterval($ttl);
        if ($interval === null) {
            return null;
        }
        if ($interval->invert === 1) {
            throw new AppCacheException(self::INVALID_TTL_MESSAGE . ': must not be negative');
        }

        try {
            $start = new DateTimeImmutable('now');
            return $start->add($interval)->getTimestamp() - $start->getTimestamp();
        } catch (Throwable $exception) {
            throw new AppCacheException(self::INVALID_TTL_MESSAGE . ': ' . $exception->getMessage(), 0, $exception);
        }
    }
}
