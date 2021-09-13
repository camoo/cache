<?php

namespace Camoo\Cache;

use Camoo\Cache\Interfaces\CacheSystemFactoryInterface;
/**
 * Class Base
 * @author CamooSarl
 */
class Base
{

    /** @var array cache Factory */
    private $cacheFactory = [CacheSystemFactory::class, 'create'];

    /**
     * @return CacheSystemFactoryInterface
     */
    protected function loadFactory(): CacheSystemFactoryInterface
    {
        return call_user_func($this->cacheFactory);
    }
}
