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
        private ?string $cryptoSalt = null,
        private ?string $dirname = null,
        private ?string $tmpPath = null,
        private ?string $prefix = null,
        private ?string $namespace = null,
        private ?string $server = '127.0.0.1',
        private ?int $port = 6379,
        private ?int $timeout = 0,
        private ?int $database = 0,
        private ?string $password = null
    ) {
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

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

        // Redis specific options
        if (str_contains($this->className, 'Redis')) {
            $options += [
                'server' => $this->server,
                'port' => $this->port,
                'timeout' => $this->timeout,
                'database' => $this->database,
                'password' => $this->password,
            ];
        }

        return $options;
    }

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
            $config['className'] ?? Filesystem::class,
            $config['duration'] ?? null,
            $config['serialize'] ?? true,
            $config['encrypt'] ?? true,
            $config['crypto_salt'] ?? null,
            $config['dirname'] ?? null,
            $config['tmpPath'] ?? null,
            $config['prefix'] ?? null,
            $config['namespace'] ?? null,
            $config['server'] ?? '127.0.0.1',
            $config['port'] ?? 6379,
            $config['timeout'] ?? 0,
            $config['database'] ?? 0,
            $config['password'] ?? null
        );
    }

    public function getClassName(): string
    {
        return $this->className;
    }

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
        return $this->encrypt && !empty($this->cryptoSalt);
    }

    public function getCryptoSalt(): ?string
    {
        return $this->cryptoSalt;
    }

    // Additional getters for Redis settings

    public function getServer(): ?string
    {
        return $this->server;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    public function getDatabase(): ?int
    {
        return $this->database;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }
}
