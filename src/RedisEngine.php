<?php

declare(strict_types=1);

namespace Camoo\Cache;

use Camoo\Cache\Interfaces\CacheInterface;
use Camoo\Cache\InvalidArgumentException as SimpleCacheInvalidArgumentException;
use DateInterval;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Throwable;

class RedisEngine extends Base implements CacheInterface
{
    private ?RedisAdapter $cache = null;

    public function __construct(array $options = [])
    {
        $this->cache ??= $this->loadFactory()->getRedisAdapter($options);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);
        $item = $this->cache->getItem($key);

        return $item->isHit() ? $item->get() : $default;
    }

    /**
     * @param mixed|null $ttl
     *
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->validateKey($key);
        $item = $this->cache->getItem($key);
        $item->set($value);
        if ($ttl !== null) {
            $item->expiresAfter($this->parseTtl($ttl));
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

    /** @throws InvalidArgumentException */
    public function has(string $key): bool
    {
        $this->validateKey($key);

        return $this->cache->hasItem($key);
    }
}
