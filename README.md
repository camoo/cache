# Camoo Cache

A flexible caching library for PHP,
supporting both FileSystem and Redis storage options with optional encryption capabilities.

## Installation

Install the package via Composer:

```bash
composer require camoo/cache
```

# Configuration

Before using Camoo Cache, you need to configure it based on your caching strategy and security preferences.

## Generating a Crypto Salt (Optional)

For encryption, generate a random crypto salt and save it securely, e.g., in an environment variable:

```php
use Defuse\Crypto\Key;

$key = Key::createNewRandomKey();
$salt = $key->saveToAsciiSafeString();

```

## Basic Usage

Import and configure the cache system, then read and write data:

```php
use Camoo\Cache\Cache;
use Camoo\Cache\CacheConfig;

// Configuration for using FileSystem with encryption
$config = CacheConfig::fromArray([
    'duration' => 3600, // Cache duration in seconds
    'crypto_salt' => $salt, // Use the generated salt for encryption
    'encrypt' => true, // Enable encryption
]);

// Configuration for using FileSystem without encryption
$configNoEncrypt = CacheConfig::fromArray([
    'duration' => '+2 weeks', // Relative format supported
    'encrypt' => false,
]);

$cache = new Cache($config);

// Writing data to the cache
$cache->write('foo', 'bar');

// Reading data from the cache
$value = $cache->read('foo');

```

## Using Redis as a Cache Backend

To use Redis, specify `RedisEngine` as the class name and provide Redis-specific configurations:

```php
$configRedis = CacheConfig::fromArray([
    'className' => \Camoo\Cache\RedisEngine::class, // Specify Redis engine
    'duration' => 3600, // TTL for cache entries
    'crypto_salt' => $salt, // Optional: for encrypted cache
    'encrypt' => true, // Enable encryption
    'server' => '127.0.0.1', // Redis server address
    'port' => 6379, // Redis server port
    'password' => 'foobar', // Redis password if required
    'database' => 0 // Redis database index
]);

$cacheRedis = new Cache($configRedis);

// Writing data to Redis
$cacheRedis->write('foo', 'data');

// Reading data from Redis
$data = $cacheRedis->read('foo');

```

## Stampede-safe Remember

Use `remember()` to compute a missing value once while Symfony's cache lock
registry coordinates concurrent requests:

```php
$value = $cache->remember(
    'expensive-result',
    static fn (): array => loadExpensiveResult(),
    60
);
```

The callback runs on a miss and its result is cached for the supplied TTL.
The optional fourth argument, `beta`, defaults to `1.0` for probabilistic early
recomputation; use `0.0` to disable early recomputation.

# Running Tests

Run the complete test suite, including Redis integration tests, with Docker:

```bash
docker compose up --build --abort-on-container-exit --exit-code-from tests
```

The Compose test image enables the PHP Redis extension and connects to the
isolated Redis service on database 15. The Redis service is not exposed on the
host, so the test instance is safe to discard after the run.

Serialized PHP classes are supported by `CacheConfig::fromArray()` by default
for backwards compatibility. Direct `CacheConfig` construction disables them
by default. Set `allow_serialized_classes` to `false` explicitly when cache
contents may be modified by an untrusted party.

# Advanced Configuration

Camoo Cache can be tailored with various settings, including namespace management, prefixing keys, and adjusting the
underlying adapter's options.
Consult the full configuration options in the `\Camoo\Cache\CacheConfig` class for more details.
