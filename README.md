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

# Advanced Configuration

Camoo Cache can be tailored with various settings, including namespace management, prefixing keys, and adjusting the
underlying adapter's options.
Consult the full configuration options in the `\Camoo\Cache\CacheConfig` class for more details.
