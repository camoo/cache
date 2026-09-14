<?php

declare(strict_types=1);

namespace Camoo\Cache\Factory;

use Camoo\Cache\Exception\AppCacheException as Exception;
use Camoo\Cache\Helper\TtlParser;
use Camoo\Cache\Interfaces\CacheSystemFactoryInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Throwable;

/**
 * Class FileSystemFactory
 *
 * @author CamooSarl
 */
final class CacheSystemFactory implements CacheSystemFactoryInterface
{
    private TtlParser $ttlParser;

    private static ?CacheSystemFactoryInterface $factory = null;

    public function __construct()
    {
        $this->ttlParser = new TtlParser();
    }

    /** creates instances of Factory */
    public static function create(): CacheSystemFactoryInterface
    {
        if (null === self::$factory) {
            self::$factory = new self();
        }

        return self::$factory;
    }

    /** @inheritDoc */
    public function getRedisAdapter(array $options = []): RedisAdapter
    {
        // Merge the default options with the provided options.
        $default = [
            'server' => '127.0.0.1',
            'port' => 6379,
            'timeout' => 0,
            'password' => null,
            'database' => 0,
        ];
        $options = array_merge($default, $options);

        $server = (string) $options['server'];
        $port = (int) $options['port'];
        $database = (int) $options['database'];
        if ($port < 1 || $port > 65535 || $database < 0 || $server === '' || preg_match('/[\s\/@]/', $server) === 1) {
            throw new Exception('Invalid Redis connection settings.');
        }

        // Encode credentials as URI components so they cannot alter the DSN.
        $passwordPart = $options['password'] !== null && $options['password'] !== ''
            ? rawurlencode((string) $options['password']) . '@'
            : '';
        $connection = sprintf(
            'redis://%s%s:%d/%d',
            $passwordPart,
            $server,
            $port,
            $database
        );

        // Check if the RedisAdapter class is available.
        if (!$this->classExists(RedisAdapter::class)) {
            throw new Exception(sprintf('Adapter Class %s cannot be found', RedisAdapter::class));
        }

        // Create and return a new RedisAdapter instance.
        try {
            $redisAdapter = new RedisAdapter(
                RedisAdapter::createConnection($connection, ['timeout' => (float) $options['timeout']]),
                $options['namespace'] ?? CacheSystemFactoryInterface::CACHE_DIRNAME,
                $this->ttlParser->toSeconds($options['ttl'] ?? CacheSystemFactoryInterface::CACHE_TTL)
            );
        } catch (Throwable $exception) {
            throw new Exception('Failed to create Redis Adapter: ' . $exception->getMessage(), 0, $exception);
        }

        return $redisAdapter;
    }

    /** @inheritDoc
     * @throws \Exception
     */
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

        return new FilesystemAdapter(
            $options['namespace'],
            $this->ttlParser->toSeconds($ttl),
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
