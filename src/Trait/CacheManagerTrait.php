<?php

declare(strict_types=1);

namespace Camoo\Cache\Trait;

use Camoo\Cache\Helper\TtlParser;
use Camoo\Cache\InvalidArgumentException as SimpleCacheInvalidArgumentException;
use DateInterval;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Contracts\Cache\CacheInterface as SymfonyCacheInterface;
use Throwable;

trait CacheManagerTrait
{
    private ?Psr16Cache $psr16Cache = null;

    private function psr16(): Psr16Cache
    {
        return $this->psr16Cache ??= new Psr16Cache($this->cache);
    }

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
            $parser = new TtlParser();
            $item->expiresAfter($parser->toDateInterval($ttl));
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
            return $this->psr16()->getMultiple($keys, $default);
        } catch (Throwable $exception) {
            throw new SimpleCacheInvalidArgumentException($exception->getMessage());
        }
    }

    /** @param iterable<string,mixed> $values */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        try {
            return $this->psr16()->setMultiple($values, $ttl);
        } catch (Throwable $exception) {
            throw new SimpleCacheInvalidArgumentException($exception->getMessage());
        }
    }

    public function deleteMultiple(iterable $keys): bool
    {
        try {
            return $this->psr16()->deleteMultiple($keys);
        } catch (Throwable $exception) {
            throw new SimpleCacheInvalidArgumentException($exception->getMessage());
        }
    }

    /**
     * Computes a missing value through Symfony's stampede-safe cache contract.
     *
     * @param callable():mixed $callback
     * @throws InvalidArgumentException|Exception
     */
    public function remember(string $key, callable $callback, mixed $ttl = null, ?float $beta = 1.0): mixed
    {
        $this->validateKey($key);
        if (!$this->cache instanceof SymfonyCacheInterface) {
            throw new SimpleCacheInvalidArgumentException('Cache adapter does not support stampede protection.');
        }

        $cache = $this->cache;

        return $cache->get(
            $key,
            static function (CacheItemInterface $item) use ($callback, $ttl): mixed {
                $value = $callback();
                if ($ttl !== null) {
                    $item->expiresAfter((new TtlParser())->toDateInterval($ttl));
                }

                return $value;
            },
            $beta
        );
    }
}
