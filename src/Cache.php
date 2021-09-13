<?php
declare(strict_types=1);

namespace Camoo\Cache;

use Camoo\Cache\Exception\AppCacheException as AppException;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Throwable;

/**
 * Class Cache
 * @author CamooSarl
 */
final class Cache
{

    /**
     * @var CacheConfig
     */
    private $config;

    public function __construct(CacheConfig $config)
    {

        $this->config = $config;
    }

    /**
     * @param string $key
     * @param string|int|array|mixed $value
     * @param int|null|string $ttl
     * @return bool
     */
    public function write(string $key, $value, $ttl = null): ?bool
    {

        $class = $this->config->getClassName();

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
        return (new $class)->set($key, $value, $ttl);
    }

    /**
     * @param string $key
     * @return null|string|int|array|mixed
     */
    public function read(string $key)
    {
        $class = $this->config->getClassName();

        $value = (new $class)->get($key);

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

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        $class = $this->config->getClassName();
        return (new $class)->delete($key);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function check(string $key): bool
    {
        $class = $this->config->getClassName();
        return (new $class)->has($key);
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        $class = $this->config->getClassName();
        return (new $class)->clear();
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws BadFormatException
     */
    protected function encrypt(string $plaintext): string
    {
        $key = Key::loadFromAsciiSafeString($this->config->getCryptoSalt());
        return Crypto::encrypt($plaintext, $key, false);
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws BadFormatException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    protected function decrypt(string $ciphertext): string
    {
        $key = Key::loadFromAsciiSafeString($this->config->getCryptoSalt());
        return Crypto::decrypt($ciphertext, $key, false);
    }

}
