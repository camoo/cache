<?php

declare(strict_types=1);

namespace Camoo\Cache;

use Camoo\Cache\Interfaces\CacheInterface;
use Camoo\Cache\InvalidArgumentException as SimpleCacheInvalidArgumentException;
use DateInterval;
use Exception;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Throwable;

class Filesystem extends Base implements CacheInterface
{
    private ?FilesystemAdapter $cache = null;

    /** @param array<string, string|int> $options */
    public function __construct(array $options = [])
    {
        $this->cache ??= $this->loadFactory()->getFileSystemAdapter($options);
    }

    /** @throws InvalidArgumentException */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        /** @var CacheItemInterface $item */
        $item = $this->cache->getItem($key);

        return $item->isHit() ? $item->get() : $default;
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->validateKey($key);
        /** @var CacheItemInterface $item */
        $item = $this->cache->getItem($key);
        if ($item->isHit()) {
            return false;
        }

        $item->set($value);

        if (null !== $ttl) {
            $item->expiresAfter($this->parseTTL($ttl));
        }

        return $this->cache->save($item);
    }

    /** @throws InvalidArgumentException */
    public function delete(string $key): bool
    {
        $this->validateKey($key);

        return $this->cache->deleteItem($key);
    }

    public function clear(): bool
    {
        return $this->cache->clear();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        try {
            return (new Psr16Cache($this->cache))->getMultiple($keys, $default);
        } catch (Throwable $exception) {
            throw new SimpleCacheInvalidArgumentException($exception->getMessage());
        }
    }

    /**
     * @param iterable<string,mixed> $values
     */
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
        } catch (Throwable $err) {
            throw new SimpleCacheInvalidArgumentException($err->getMessage());
        }
    }

    /** @throws InvalidArgumentException */
    public function has(string $key): bool
    {
        $this->validateKey($key);

        return $this->cache->hasItem($key);
    }
}
