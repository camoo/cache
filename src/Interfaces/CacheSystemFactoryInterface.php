<?php

declare(strict_types=1);

namespace Camoo\Cache\Interfaces;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * Interface FileSystemFactoryInterface
 *
 * @author CamooSarl
 */
interface CacheSystemFactoryInterface
{
    public const CACHE_DIRNAME = 'persistent';

    public const CACHE_TTL = 300;

    /**
     * @param array<string,string|int> $options
     */
    public function getFileSystemAdapter(array $options = []): FilesystemAdapter;

    /**
     * @param array<string,string|int> $options
     */
    public function getRedisAdapter(array $options = []): RedisAdapter;
}
