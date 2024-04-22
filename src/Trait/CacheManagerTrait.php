<?php

declare(strict_types=1);

namespace Camoo\Cache\Trait;

use Camoo\Cache\InvalidArgumentException as SimpleCacheInvalidArgumentException;
use DateInterval;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Psr16Cache;
use Throwable;

trait CacheManagerTrait
{
    /** @throws InvalidArgumentException */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);
        $item = $this->cache?->getItem($key);

        if (null === $item) {
            return $default;
        }

        return $item->isHit() ? $item->get() : $default;
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->validateKey($key);
        $item = $this->cache?->getItem($key);
        if (null === $item) {
            return false;
        }
        $item->set($value);
        if ($ttl !== null) {
            $item->expiresAfter($this->parseTtl($ttl));
        }

        return (bool)$this->cache?->save($item);
    }

    /** @throws InvalidArgumentException */
    public function delete(string $key): bool
    {
        $this->validateKey($key);

        return (bool)$this->cache?->deleteItem($key);
    }

    /** @throws InvalidArgumentException */
    public function has(string $key): bool
    {
        $this->validateKey($key);

        return (bool)$this->cache?->hasItem($key);
    }

    public function clear(): bool
    {
        return (bool)$this->cache?->clear();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        if (null === $this->cache) {
            throw new SimpleCacheInvalidArgumentException('Cache not initialized');
        }
        try {
            return (new Psr16Cache($this->cache))->getMultiple($keys, $default);
        } catch (Throwable $exception) {
            throw new SimpleCacheInvalidArgumentException($exception->getMessage());
        }
    }

    /** @param iterable<string,mixed> $values */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        try {
            return (new Psr16Cache($this->cache))->setMultiple($values, $ttl);
        } catch (Throwable $exception) {
            throw new SimpleCacheInvalidArgumentException($exception->getMessage());
        }
    }

    public function deleteMultiple(iterable $keys): bool
    {
        try {
            return (new Psr16Cache($this->cache))->deleteMultiple($keys);
        } catch (Throwable $exception) {
            throw new SimpleCacheInvalidArgumentException($exception->getMessage());
        }
    }
}
