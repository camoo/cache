<?php

declare(strict_types=1);

namespace Camoo\Cache;

use Camoo\Cache\Exception\AppCacheException;
use Camoo\Cache\Exception\AppCacheException as AppException;
use Camoo\Cache\Helper\TtlParser;
use Camoo\Cache\Interfaces\CacheInterface;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Exception;
use Psr\SimpleCache\InvalidArgumentException;
use stdClass;
use Throwable;

/**
 * Class Cache
 *
 * @method static bool      deletes(string $key, string $config)
 * @method static mixed     reads(string $key, string $config)
 * @method static bool|null writes(string $key, mixed $value, string $config)
 * @method static bool      checks(string $key, string $config)
 * @method static bool      clears(string $config)
 *
 * @author CamooSarl
 */
class Cache
{
    private CacheInterface $adapter;

    private ?CacheConfig $config;

    private TtlParser $ttpParser;

    public function __construct(?CacheConfig $config = null)
    {
        $this->config = $config;
        if ($this->config !== null) {
            $this->initializeAdapter();
        }
        $this->ttpParser = new TtlParser();
    }

    /**
     * @param string[] $arguments
     *
     * @throws InvalidArgumentException
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        if (method_exists(self::class, $method)) {
            throw new AppException(sprintf('Method "%s" not found!', get_called_class() . '::' . $method));
        }

        if (!in_array($method, ['reads', 'writes', 'deletes', 'clears', 'checks'], true)) {
            throw new AppException(sprintf('Method "%s" not accessible!', get_called_class() . '::' . $method));
        }

        return self::applyMagicCall($arguments, $method);
    }

    public function withConfig(CacheConfig $config): self
    {
        // Clone the current instance to keep it immutable
        $newInstance = clone $this;

        $newInstance->config = $config;
        $this->initializeAdapter();

        return $newInstance;
    }

    /**
     * @param string|int|array|mixed $value
     * @param int|string|null        $ttl
     *
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function write(string $key, mixed $value, mixed $ttl = null): ?bool
    {
        $this->ensureConfigured();

        $value = $this->prepareValueForStorage($value);

        $ttl = $ttl ?? $this->config?->getDuration();

        return $this->adapter->set($this->formatKey($key), $value, $this->ttpParser->toDateInterval($ttl));
    }

    /** @throws InvalidArgumentException */
    public function read(string $key): mixed
    {
        $this->ensureConfigured();

        $value = $this->adapter->get($this->formatKey($key));

        return $this->prepareValueFromStorage($value);
    }

    /** @throws InvalidArgumentException */
    public function delete(string $key): bool
    {
        $this->ensureConfigured();

        return $this->adapter->delete($this->formatKey($key));
    }

    /** @throws InvalidArgumentException */
    public function check(string $key): bool
    {
        $this->ensureConfigured();

        return $this->adapter->has($this->formatKey($key));
    }

    public function clear(): bool
    {
        return $this->adapter->clear();
    }

    private function ensureConfigured(): void
    {
        if ($this->config === null) {
            throw new AppCacheException('Cache is not configured properly.');
        }
    }

    private function initializeAdapter(): void
    {
        if ($this->config === null) {
            throw new AppException('Configuration must be set before initializing the adapter.');
        }

        $class = $this->config->getClassName();
        if (!class_exists($class) || !in_array(CacheInterface::class, class_implements($class))) {

            throw new AppException('Cache adapter class ' . $class . ' not found.');
        }
        $this->adapter = new $class($this->config->getOptions());
    }

    private function prepareValueForStorage(mixed $value): string
    {
        if ($this->config?->withSerialization()) {
            $value = serialize($value);
        }

        if ($this->config?->withEncryption()) {
            try {
                $value = $this->encrypt($value);
            } catch (Throwable $exception) {
                throw new AppException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
            }
        }

        return $value;
    }

    private function prepareValueFromStorage(?string $value): mixed
    {
        if ($value === null) {
            return false;
        }

        if (!empty($value) && $this->config?->withEncryption()) {
            try {
                $value = $this->decrypt($value);
            } catch (Throwable $exception) {
                throw new AppException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
            }
        }

        if (!empty($value) && $this->config?->withSerialization()) {
            return unserialize($value);
        }

        return $value;
    }

    private function encrypt(string $plaintext): string
    {
        try {

            $key = Key::loadFromAsciiSafeString($this->config?->getCryptoSalt() ?? '');

            return Crypto::encrypt($plaintext, $key);
        } catch (Throwable $exception) {
            throw new AppException('Encryption failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    private function decrypt(string $ciphertext): string
    {
        try {
            $key = Key::loadFromAsciiSafeString($this->config?->getCryptoSalt() ?? '');

            return Crypto::decrypt($ciphertext, $key);
        } catch (Throwable $exception) {
            throw new AppException('Decryption failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /** @return array<string,mixed> */
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

        if (array_key_exists('className', $configData)) {
            unset($configData['className']);
        }

        $configData += $default;

        if (!class_exists($configData['CacheConfig'])) {
            throw new AppException(sprintf('ClassName %s Not found !', $configData['CacheConfig']));
        }
        if (empty($configData['tmpPath']) && !empty($configData['path'])) {
            $configData['tmpPath'] = $configData['path'];
        }

        return $configData;
    }

    private function formatKey(string $key): string
    {
        if (empty($this->config?->getPrefix())) {
            return $key;
        }

        return $this->config->getPrefix() . $key;
    }

    /** @param string[] $arguments */
    private static function parseArguments(stdClass $data, array $arguments, string $method): stdClass
    {
        if ($method === 'writes') {
            [$key, $value, $config] = $arguments;
            $data->value = $value;
        } elseif ($method === 'clears') {
            [$config] = $arguments;
        } else {
            [$key, $config] = $arguments;
        }

        if (empty($config)) {
            throw new AppException('\$config cannot be empty!');
        }
        if (isset($key)) {
            $data->key = (string)$key;
        }

        $data->config = (string)$config;

        return $data;
    }

    /**
     * @param string[] $rawArguments
     *
     * @throws InvalidArgumentException
     */
    private static function applyMagicCall(array $rawArguments, string $method): mixed
    {
        $data = new stdClass();
        $parsedData = self::parseArguments($data, $rawArguments, $method);
        $cacheConfig = CacheConfig::fromArray(self::getConfig($parsedData->config));
        $cache = new self($cacheConfig);
        $adaptedMethod = rtrim($method, 's');
        if (!method_exists($cache, $adaptedMethod)) {
            throw new AppException('Method ' . $adaptedMethod . ' not found in Cache class.');
        }
        $arguments = $method !== 'clears' ? [$parsedData->key] : [];
        if ($method === 'writes') {
            $arguments[] = $parsedData->value;
        }
        $arguments[] = $parsedData->config;

        if ($method === 'writes') {
            return $cache->write($parsedData->key, $parsedData->value);
        }

        return call_user_func_array([$cache, $adaptedMethod], $arguments);
    }
}
