<?php
declare(strict_types=1);

namespace Camoo\Cache;

class CacheConfig
{

    /**
     * @var string
     */
    private $className;
    private $duration;
    /**
     * @var bool
     */
    private $serialize;
    /**
     * @var bool|mixed
     */
    private $encrypt;
    /**
     * @var string|null
     */
    private $cryptoSalto;

    public function __construct(
        string  $className,
                $duration = null,
        bool    $serialize = true,
                $encrypt = true,
        ?string $cryptoSalto = null
    )
    {
        $this->className = $className;
        $this->duration = $duration;
        $this->serialize = $serialize;
        $this->encrypt = $encrypt;
        $this->cryptoSalto = $cryptoSalto;
    }

    public static function fromArray(array $config): CacheConfig
    {
        return new self(
            $config['CacheConfig'] ?? Filesystem::class,
            $config['duration'] ?? null,
            $config['serialize'] ?? true,
            $config['encrypt'] ?? true,
            $config['crypto_salt'] ?? null
        );
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return mixed
     */
    public function getDuration()
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
