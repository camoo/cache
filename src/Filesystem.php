<?php

declare(strict_types=1);

namespace Camoo\Cache;

use Camoo\Cache\Interfaces\CacheInterface;
use Camoo\Cache\Trait\CacheManagerTrait;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class Filesystem extends Base implements CacheInterface
{
    use CacheManagerTrait;

    private ?FilesystemAdapter $cache = null;

    /** @param array<string, string|int> $options */
    public function __construct(array $options = [])
    {
        $this->cache ??= $this->loadFactory()->getFileSystemAdapter($options);
    }
}
