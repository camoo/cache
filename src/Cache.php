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

    /** @return bool */
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
}
