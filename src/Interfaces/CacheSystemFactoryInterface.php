<?php

namespace Camoo\Cache\Interfaces;

/**
 * Interface FileSystemFactoryInterface
 *
 * @author CamooSarl
 */
interface CacheSystemFactoryInterface
{
    public const CACHE_DIRNAME = 'persistent';

    public const CACHE_TTL = 300;
}
