<?php

declare(strict_types=1);

namespace Camoo\Cache\Tests;

use Camoo\Cache\Cache;
use Camoo\Cache\CacheConfig;
use Camoo\Cache\Exception\AppCacheException;
use Camoo\Cache\Filesystem;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    private Cache $cache;

    private CacheConfig $config;

    protected function setUp(): void
    {

        $this->config = CacheConfig::fromArray([
            'crypto_salt' => 'def0000077a07f6739564bffe80f095089e42e8792148231ed0906152b957f8532a1e8fe92d90b8f640e68163b5c4dd20102ae0fbaeccfabdb874cf490940081f79d50ac',
            'serialize' => true,
            'encrypt' => true,
        ]);
        $this->cache = new Cache($this->config);
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
    }

    public function testWriteAndRead(): void
    {
        $key = 'testKey';
        $value = 'testValue';
        $this->assertTrue($this->cache->write($key, $value));
        $this->assertEquals($value, $this->cache->read($key));
        $this->assertFalse($this->cache->read('nonexistentKey'));
    }

    public function testDelete(): void
    {
        $key = 'testKey';
        $value = 'value';
        $this->cache->write($key, $value);
        $this->assertTrue($this->cache->delete($key));
        $this->assertFalse($this->cache->read($key));
    }

    public function testClear(): void
    {
        $this->cache->write('key1', 'value1');
        $this->cache->write('key2', 'value2');
        $this->assertTrue($this->cache->clear());
        $this->assertFalse($this->cache->read('key1'));
        $this->assertFalse($this->cache->read('key2'));
    }

    public function testCheck(): void
    {
        $key = 'existing_key';
        $this->cache->write($key, 'value');
        $this->assertTrue($this->cache->check($key));
        $this->cache->delete($key);
        $this->assertFalse($this->cache->check($key));
    }

    public function testThrowsExceptionIfNotConfigured(): void
    {
        $this->expectException(AppCacheException::class);
        $cache = new Cache();
        $cache->read('nonexistentKey');
    }

    public function testClearThrowsDomainExceptionIfNotConfigured(): void
    {
        $this->expectException(AppCacheException::class);
        (new Cache())->clear();
    }

    public function testSerializedObjectsAreHydratedByDefaultForCompatibility(): void
    {
        $config = CacheConfig::fromArray([
            'serialize' => true,
            'encrypt' => false,
            'dirname' => 'security-test',
            'tmpPath' => sys_get_temp_dir(),
        ]);
        $cache = new Cache($config);
        $cache->write('object', new \stdClass());

        $value = $cache->read('object');

        $this->assertInstanceOf(\stdClass::class, $value);
        $cache->clear();
    }

    public function testSerializedObjectsCanBeDisabled(): void
    {
        $config = CacheConfig::fromArray([
            'serialize' => true,
            'encrypt' => false,
            'allow_serialized_classes' => false,
            'dirname' => 'security-test-disabled',
            'tmpPath' => sys_get_temp_dir(),
        ]);
        $cache = new Cache($config);
        $cache->write('object', new \stdClass());

        $this->assertNotInstanceOf(\stdClass::class, $cache->read('object'));
        $cache->clear();
    }

    public function testCanApplyWithConfig(): void
    {
        $originalCache = $this->cache;
        $filesystemConfig = new CacheConfig(Filesystem::class, null, true, false, null, 'with-config-test', sys_get_temp_dir());
        $newCache = $this->cache->withConfig($filesystemConfig);
        $this->assertNotSame($originalCache, $newCache);
    }
}
