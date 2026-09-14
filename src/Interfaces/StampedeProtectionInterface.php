<?php

declare(strict_types=1);

namespace Camoo\Cache\Interfaces;

interface StampedeProtectionInterface
{
    /** @param callable():mixed $callback */
    public function remember(string $key, callable $callback, mixed $ttl = null, ?float $beta = 1.0): mixed;
}
