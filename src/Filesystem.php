<?php
declare(strict_types=1);

namespace Camoo\Cache;

use Exception;
use Symfony\Component\Cache\Psr16Cache;
use Camoo\Cache\Interfaces\CacheInterface;
use DateTime;
use DateInterval;
use Throwable;

class Filesystem extends Base implements CacheInterface
{
    private $oCache = null;
    private const INVALID_MESSAGE = 'key is not a legal value';

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if ($this->oCache === null) {
            $this->oCache = $this->loadFactory()->getFileSystemAdapter($options);
        }
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws InvalidArgumentException|\Psr\Cache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function get($key, $default = null)
    {
        if (!is_string($key) || trim($key) === '') {
            throw new InvalidArgumentException(self::INVALID_MESSAGE);
        }
        if (!$this->has($key)) {
            return $default;
        }
        $cache = $this->oCache->getItem($key);
        return $cache->get($key, $default);
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store, must be serializable.
     * @param null|int|DateInterval|string $ttl Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException|Exception
     * @throws \Psr\Cache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function set($key, $value, $ttl = null): ?bool
    {
        if (!is_string($key) || trim($key) === '') {
            throw new InvalidArgumentException(self::INVALID_MESSAGE);
        }

        if (is_string($ttl) && preg_match('/^\+/', $ttl)) {
            try {
                $oNow = new DateTime('now');
                $sec = $oNow->modify($ttl)->getTimestamp() - time();
                if ($sec < 0) {
                    throw new InvalidArgumentException("ttl is not a legal value");
                }
                $ttl = new DateInterval(sprintf('PT%dS', $sec));
            } catch (Throwable $exception) {
                throw new InvalidArgumentException("ttl is not a legal value");
            }
        }

        $cache = $this->oCache->getItem($key);
        if ($cache->isHit()) {
            return null;
        }

        $cache->set($value);
        if (!empty($ttl)) {
            $cache->expiresAfter($ttl);
        }
        return $this->oCache->save($cache);

    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws InvalidArgumentException|\Psr\Cache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function delete($key): bool
    {
        if (!is_string($key) || trim($key) === '') {
            throw new InvalidArgumentException(self::INVALID_MESSAGE);
        }

        return $this->oCache->deleteItem($key);
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear(): bool
    {
        return $this->oCache->clear();
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys A list of keys that can obtained in a single operation.
     * @param mixed $default Default value to return for keys that do not exist.
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function getMultiple($keys, $default = null): iterable
    {
        try {
            return (new Psr16Cache($this->oCache))->getMultiple($keys, $default);
        } catch (Throwable $err) {
            throw new InvalidArgumentException($err->getMessage());
        }
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     */
    public function setMultiple($values, $ttl = null): bool
    {
        try {
            return (new Psr16Cache($this->oCache))->setMultiple($values, $ttl);
        } catch (Throwable $err) {
            throw new InvalidArgumentException($err->getMessage());
        }
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function deleteMultiple($keys): bool
    {
        try {
            return (new Psr16Cache($this->oCache))->deleteMultiple($keys);
        } catch (Throwable $err) {
            throw new InvalidArgumentException($err->getMessage());
        }
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws InvalidArgumentException|\Psr\Cache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function has($key): bool
    {
        return $this->oCache->hasItem($key);
    }
}
