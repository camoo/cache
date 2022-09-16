<?php

declare(strict_types=1);

namespace Camoo\Cache;

use Camoo\Cache\Exception\AppCacheException as Exception;
use Camoo\Cache\Interfaces\CacheSystemFactoryInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Class FileSystemFactory
 *
 * @author CamooSarl
 */
final class CacheSystemFactory implements CacheSystemFactoryInterface
{
    private static ?CacheSystemFactoryInterface $_created = null;

    /** creates instances of Factory */
    public static function create(): CacheSystemFactoryInterface
    {
        if (null === self::$_created) {
            self::$_created = new self();
        }

        return self::$_created;
    }

    public function getFileSystemAdapter(array $options = []): FilesystemAdapter
    {
        $default = [
            'namespace' => CacheSystemFactoryInterface::CACHE_DIRNAME,
            'ttl' => CacheSystemFactoryInterface::CACHE_TTL,
            'dirname' => CacheSystemFactoryInterface::CACHE_DIRNAME,
            'tmpPath' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR,
        ];
        $options = array_merge($default, $options);
        if (!$this->classExists(FilesystemAdapter::class)) {
            throw new Exception(sprintf(
                'Adapter Class %s cannot be found',
                'Symfony\Component\Cache\Adapter\FilesystemAdapter'
            ));
        }

        return new FilesystemAdapter(
            $options['namespace'],
            $options['ttl'],
            rtrim($options['tmpPath'], DIRECTORY_SEPARATOR) .
            DIRECTORY_SEPARATOR .
            trim($options['dirname'], DIRECTORY_SEPARATOR)
        );
    }

    /** @param string $name class name */
    protected function classExists(string $name): bool
    {
        return class_exists($name);
    }
}
