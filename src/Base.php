<?php

declare(strict_types=1);

namespace Camoo\Cache;

use Camoo\Cache\Interfaces\CacheSystemFactoryInterface;

/**
 * Class Base
 *
 * @author CamooSarl
 */
class Base
{
    protected function loadFactory(): CacheSystemFactoryInterface
    {
        return CacheSystemFactory::create();
    }
}
