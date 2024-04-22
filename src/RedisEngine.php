<?php

declare(strict_types=1);

namespace Camoo\Cache;

use Camoo\Cache\Interfaces\CacheInterface;
use Camoo\Cache\Trait\CacheManagerTrait;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class RedisEngine extends Base implements CacheInterface
{
    use CacheManagerTrait;

    private ?RedisAdapter $cache = null;

    /** @param array<string,string|int> $options */
    public function __construct(array $options = [])
    {
        $this->cache ??= $this->loadFactory()->getRedisAdapter($options);
    }
}
