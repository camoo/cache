# Camoo Cache FileSystem

## Installation

```shell
composer require camoo/cache
```

## Usage
```php
use Camoo\Cache\CacheConfig;
use Defuse\Crypto\Key;

// In order to encryption you need to generate a random crypto salt and save it. e.g: in .env file
$key = Key::createNewRandomKey();
$salt = $key->saveToAsciiSafeString();
$config = CacheConfig::fromArray(['duration' => 3600, 'crypto_salt' => $salt]);

// without encryption
$config = CacheConfig::fromArray(['duration' => '+ 2 weeks', 'encrypt' => false]);

$cache = new Camoo\Cache\Cache($config);

// write into cache
$cache->write('foo', 'bar');

// read from cache
$cache->read('foo');




```
