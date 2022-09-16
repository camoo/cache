<?php

declare(strict_types=1);

namespace Camoo\Cache;

class CacheConfig
{
    public function __construct(
        private string $className,
        private mixed $duration = null,
        private bool $serialize = true,
        private bool $encrypt = true,
        private ?string $cryptoSalto = null,
        private ?string $dirname = null,
        private ?string $tmpPath = null,
        private ?string $namespace = null
    ) {
    }

    /** @return string|null */
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    /** @return array */
    public function getOptions(): array
    {
        $options = ['ttl' => $this->getDuration()];
        if (null !== $this->getNamespace()) {
            $options['namespace'] = $this->getNamespace();
        }

        if (null !== $this->getDirname()) {
            $options['dirname'] = $this->getDirname();
        }

        if (null !== $this->getTmpPath()) {
            $options['tmpPath'] = $this->getTmpPath();
        }

        return $options;
    }

    /** @return string|null */
    public function getTmpPath(): ?string
    {
        return $this->tmpPath;
    }

    public function getDirname(): ?string
    {
        return $this->dirname;
    }

    public static function fromArray(array $config): CacheConfig
    {
        return new self(
            $config['CacheConfig'] ?? Filesystem::class,
            $config['duration'] ?? null,
            $config['serialize'] ?? true,
            $config['encrypt'] ?? true,
            $config['crypto_salt'] ?? null,
            $config['dirname'] ?? '',
            $config['tmpPath'] ?? null,
            $config['namespace'] ?? null
        );
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /** @return mixed */
    public function getDuration(): mixed
    {
        return $this->duration;
    }

    public function withSerialization(): bool
    {
        return $this->serialize;
    }

    public function withEncryption(): bool
    {
        return $this->encrypt;
    }

    public function getCryptoSalt(): ?string
    {
        return $this->cryptoSalto;
    }
}
