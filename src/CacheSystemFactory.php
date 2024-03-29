<?php

declare(strict_types=1);

namespace Camoo\Cache;

use Camoo\Cache\Exception\AppCacheException as Exception;
use Camoo\Cache\Interfaces\CacheSystemFactoryInterface;
use DateInterval;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Throwable;

/**
 * Class FileSystemFactory
 *
 * @author CamooSarl
 */
final class CacheSystemFactory implements CacheSystemFactoryInterface
{
    private static ?CacheSystemFactoryInterface $factory = null;

    /** creates instances of Factory */
    public static function create(): CacheSystemFactoryInterface
    {
        if (null === self::$factory) {
            self::$factory = new self();
        }

        return self::$factory;
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
            throw new Exception(sprintf('Adapter Class %s cannot be found', FilesystemAdapter::class));
        }

        $ttl = $options['ttl'] ?? CacheSystemFactoryInterface::CACHE_TTL;

        if (is_string($ttl) && preg_match('/^\+/', $ttl)) {
            try {
                $oNow = new \DateTime('now');
                $sec = $oNow->modify($ttl)->getTimestamp() - time();
                if ($sec < 0) {
                    throw new InvalidArgumentException('ttl is not a legal value');
                }
                $ttl = new DateInterval(sprintf('PT%dS', $sec));
                $ttl = $ttl->s;
            } catch (Throwable) {
                throw new InvalidArgumentException('ttl is not a legal value');
            }
        }

        return new FilesystemAdapter(
            $options['namespace'],
            $ttl,
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
