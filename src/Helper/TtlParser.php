<?php

declare(strict_types=1);

namespace Camoo\Cache\Helper;

use Camoo\Cache\Exception\AppCacheException;
use DateInterval;
use DateTime;
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
            return $ttl;
        }

        if (is_string($ttl)) {
            if (!preg_match('/^\+/', $ttl)) {
                throw new AppCacheException(self::INVALID_TTL_MESSAGE . ': Must start with +');
            }

            try {
                $now = new DateTime('now');
                $modifiedTime = $now->modify($ttl);
                if ($modifiedTime === false) {
                    throw new AppCacheException('Failed to modify DateTime with string: ' . $ttl);
                }

                $seconds = $modifiedTime->getTimestamp() - time();
                if ($seconds < 0) {
                    throw new AppCacheException('Calculated negative TTL from DateTime modification.');
                }

                $ttl = new DateInterval(sprintf('PT%dS', $seconds));
            } catch (Throwable $exception) {
                throw new AppCacheException(self::INVALID_TTL_MESSAGE . ': ' . $exception->getMessage(), 0, $exception);
            }
        }

        return is_int($ttl) ? new DateInterval(sprintf('PT%dS', $ttl)) : $ttl;
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

        return $this->toDateInterval($ttl)->s;
    }
}
