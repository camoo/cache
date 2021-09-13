<?php
declare(strict_types=1);

namespace Camoo\Cache;

use Camoo\Cache\Interfaces\CacheSystemFactoryInterface;
use Camoo\Cache\Exception\AppCacheException as Exception;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Class FileSystemFactory
 * @author CamooSarl
 */
final class CacheSystemFactory implements CacheSystemFactoryInterface
{

    /** @var CacheSystemFactoryInterface|null $_created */
    private static $_created = null;

    /**
     * creates instances of Factory
     * @return CacheSystemFactoryInterface
     */
    public static function create() : CacheSystemFactoryInterface
    {
        if (null === self::$_created) {
            self::$_created = new self;
        }

        return self::$_created;
    }

    /**
     * @param string $name class name
     * @return bool
     */
    protected function classExists(string $name): bool
    {
        return class_exists($name);
    }

    /**
     * @param array $options
     *
     * @return FilesystemAdapter
     */
    public function getFileSystemAdapter(array $options = []) : FilesystemAdapter
    {
        $default = [
            'namespace' => CacheSystemFactoryInterface::CACHE_DIRNAME,
            'ttl'		=> CacheSystemFactoryInterface::CACHE_TTL,
            'dirname'   => CacheSystemFactoryInterface::CACHE_DIRNAME,
            'tmpPath'   => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR,
        ];
        $options = array_merge($default, $options);
        if (!$this->classExists(FilesystemAdapter::class)) {
            throw new Exception(sprintf('Adapter Class %s cannot be found',
                'Symfony\Component\Cache\Adapter\FilesystemAdapter'));
        }
        return new FilesystemAdapter(
            $options['namespace'],
            $options['ttl'],
            $options['tmpPath']. $options['dirname']
        );
    }
}
