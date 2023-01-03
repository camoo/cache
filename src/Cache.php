<?php

declare(strict_types=1);

namespace Camoo\Cache;

use Camoo\Cache\Exception\AppCacheException as AppException;
use Camoo\Cache\Interfaces\CacheInterface;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Psr\SimpleCache\InvalidArgumentException;
use stdClass;
use Throwable;

/**
 * Class Cache
 *
 * @author CamooSarl
 */
class Cache
{
    private CacheInterface $adapter;

    public function __construct(private CacheConfig $config)
    {
        $class = $this->config->getClassName();
        $this->adapter = (new $class($this->config->getOptions()));
    }

    public static function __callStatic(string $method, array $arguments): mixed
    {
        if (method_exists(self::class, $method)) {
            throw new AppException(sprintf('Method "%s" not found!', get_called_class() . '::' . $method));
        }

        if (!in_array($method, ['read', 'write', 'delete', 'clear', 'check'], true)) {
            throw new AppException(sprintf('Method "%s" not accessible!', get_called_class() . '::' . $method));
        }

        return self::applyMagicCall($arguments, $method);
    }

    /**
     * @param string|int|array|mixed $value
     * @param int|string|null        $ttl
     *
     * @throws InvalidArgumentException
     */
    public function write(string $key, mixed $value, mixed $ttl = null): ?bool
    {
        if ($this->config->withSerialization() === true) {
            $value = serialize($value);
        }

        if ($this->config->withEncryption() === true) {
            try {
                $value = $this->encrypt($value);
            } catch (Throwable $exception) {
                throw new AppException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
            }
        }

        $ttl = $ttl ?? $this->config->getDuration();

        return $this->adapter->set($this->formatKey($key), $value, $ttl);
    }

    /** @throws InvalidArgumentException */
    public function read(string $key): mixed
    {
        $value = $this->adapter->get($this->formatKey($key));

        if (!empty($value) && $this->config->withEncryption() === true) {
            try {
                $value = $this->decrypt($value);
            } catch (Throwable $exception) {
                throw new AppException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
            }
        }

        if (!empty($value) && $this->config->withSerialization() === true) {
            $value = unserialize($value);
        }

        return null !== $value ? $value : false;
    }

    /** @throws InvalidArgumentException */
    public function delete(string $key): bool
    {
        return $this->adapter->delete($this->formatKey($key));
    }

    /** @throws InvalidArgumentException */
    public function check(string $key): bool
    {
        return $this->adapter->has($this->formatKey($key));
    }

    public function clear(): bool
    {
        return $this->adapter->clear();
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws BadFormatException
     */
    protected function encrypt(string $plaintext): string
    {
        $key = Key::loadFromAsciiSafeString($this->config->getCryptoSalt());

        return Crypto::encrypt($plaintext, $key);
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws BadFormatException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    protected function decrypt(string $ciphertext): string
    {
        $key = Key::loadFromAsciiSafeString($this->config->getCryptoSalt());

        return Crypto::decrypt($ciphertext, $key);
    }

    private function formatKey(string $key): string
    {
        if (empty($this->config->getPrefix())) {
            return $key;
        }

        return $this->config->getPrefix() . $key;
    }

    private static function getConfig(string $config): array
    {
        if (!class_exists(\CAMOO\Utils\Configure::class)) {
            throw new AppException('Class "Configure" not found! Consider to use Camoo Framework.');
        }
        $default = ['CacheConfig' => Filesystem::class, 'encrypt' => false];
        if (!\CAMOO\Utils\Configure::check('Cache.' . $config)) {
            throw new AppException(sprintf('Cache Configuration %s is missing', $config));
        }
        $configData = \CAMOO\Utils\Configure::read('Cache.' . $config);
        $configData += $default;
        $class = $configData['className'];
        if (!class_exists($class)) {
            throw new AppException(sprintf('ClassName %s Not found !', $class));
        }

        if (empty($configData['tmpPath']) && !empty($configData['path'])) {
            $configData['tmpPath'] = $configData['path'];
        }

        return $configData;
    }

    private static function parseArguments(stdClass $data, array $arguments, string $method): stdClass
    {
        if ($method === 'write') {
            [$key, $value, $config] = $arguments;
            $data->value = $value;
        } else {
            [$key, $config] = $arguments;
        }

        if (empty($key) || empty($config)) {
            throw new AppException('\$key or \$config cannot be empty!');
        }
        $data->key = (string)$key;
        $data->config = (string)$config;

        return $data;
    }

    private static function applyMagicCall(array $rawArguments, string $method): mixed
    {
        $data = new stdClass();
        $parsedData = self::parseArguments($data, $rawArguments, $method);
        $cacheConfig = CacheConfig::fromArray(self::getConfig($parsedData->config));
        $cache = new self($cacheConfig);
        $arguments = [$parsedData->key];
        if ($method === 'write') {
            $arguments[] = $parsedData->value;
        }
        $parsedData[] = $parsedData->config;

        return call_user_func_array([$cache, $method], $arguments);
    }
}
