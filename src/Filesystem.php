<?php

declare(strict_types=1);

namespace Camoo\Cache;

use Camoo\Cache\Interfaces\CacheInterface;
use Camoo\Cache\InvalidArgumentException as SimpleCacheInvalidArgumentException;
use DateInterval;
use Exception;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Throwable;

class Filesystem extends Base implements CacheInterface
{
    private ?FilesystemAdapter $cache = null;

    public function __construct(array $options = [])
    {
        $this->cache ??= $this->loadFactory()->getFileSystemAdapter($options);
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key     The unique key of this item in the cache.
     * @param mixed  $default Default value to return if the key does not exist.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws SimpleCacheInvalidArgumentException|\Psr\Cache\InvalidArgumentException
     *                                                                                 MUST be thrown if the $key string is not a legal value.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     */
    public function get($key, $default = null)
    {
        $this->validateKey($key);

        /** @var CacheItemInterface $item */
        $item = $this->cache->getItem($key);

        return $item->isHit() ? $item->get() : $default;
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string                       $key   The key of the item to store.
     * @param mixed                        $value The value of the item to store, must be serializable.
     * @param int|DateInterval|string|null $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                            the driver supports TTL then the library may set a default value
     *                                            for it or let the driver take care of that.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException|Exception
     * @throws \Psr\Cache\InvalidArgumentException
     *                                                             MUST be thrown if the $key string is not a legal value.
     *
     * @return ?bool True on success and false on failure.
     */
    public function set($key, $value, $ttl = null): ?bool
    {
        $this->validateKey($key);
        /** @var CacheItemInterface $item */
        $item = $this->cache->getItem($key);
        if ($item->isHit()) {
            return null;
        }

        $item->set($value);

        if (null !== $ttl) {
            $item->expiresAfter($this->parseTTL($ttl));
        }

        return $this->cache->save($item);
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @throws SimpleCacheInvalidArgumentException|\Psr\Cache\InvalidArgumentException
     *                                                                                 MUST be thrown if the $key string is not a legal value.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     */
    public function delete($key): bool
    {
        $this->validateKey($key);

        return $this->cache->deleteItem($key);
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear(): bool
    {
        return $this->cache->clear();
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys    A list of keys that can obtained in a single operation.
     * @param mixed    $default Default value to return for keys that do not exist.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *                                                   MUST be thrown if $keys is neither an array nor a Traversable,
     *                                                   or if any of the $keys are not a legal value.
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     */
    public function getMultiple($keys, $default = null): iterable
    {
        try {
            return (new Psr16Cache($this->cache))->getMultiple($keys, $default);
        } catch (Throwable $exception) {
            throw new SimpleCacheInvalidArgumentException($exception->getMessage());
        }
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable              $values A list of key => value pairs for a multiple-set operation.
     * @param int|DateInterval|null $ttl    Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *                                                   MUST be thrown if $values is neither an array nor a Traversable,
     *                                                   or if any of the $values are not a legal value.
     *
     * @return bool True on success and false on failure.
     */
    public function setMultiple($values, $ttl = null): bool
    {
        try {
            return (new Psr16Cache($this->cache))->setMultiple($values, $ttl);
        } catch (Throwable $exception) {
            throw new SimpleCacheInvalidArgumentException($exception->getMessage());
        }
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *                                                   MUST be thrown if $keys is neither an array nor a Traversable,
     *                                                   or if any of the $keys are not a legal value.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     */
    public function deleteMultiple($keys): bool
    {
        try {
            return (new Psr16Cache($this->cache))->deleteMultiple($keys);
        } catch (Throwable $err) {
            throw new SimpleCacheInvalidArgumentException($err->getMessage());
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
     * @throws SimpleCacheInvalidArgumentException|\Psr\Cache\InvalidArgumentException
     *                                                                                 MUST be thrown if the $key string is not a legal value.
     */
    public function has($key): bool
    {
        $this->validateKey($key);

        return $this->cache->hasItem($key);
    }
}
