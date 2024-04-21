<?php

declare(strict_types=1);

namespace Camoo\Cache\Tests;

use Camoo\Cache\RedisEngine;
use PHPUnit\Framework\TestCase;

class RedisEngineTest extends TestCase
{
    private RedisEngine $cacheEngine;

    protected function setUp(): void
    {
        $options = [
            'server' => '127.0.0.1',
            'port' => 6379,
            //'password' => 'password', // Ensure this is aligned with your Redis setup or environment
            'database' => 0,
        ];
        $this->cacheEngine = new RedisEngine($options);
    }

    protected function tearDown(): void
    {
        $this->cacheEngine->clear();
    }

    public function testSetAndGet()
    {
        $key = 'testKey';
        $value = 'testValue';
        $result = $this->cacheEngine->set($key, $value);
        $this->assertTrue($result);

        $cachedValue = $this->cacheEngine->get($key);
        $this->assertEquals($value, $cachedValue);
    }

    public function testDelete()
    {
        $key = 'testKey';
        $this->cacheEngine->set($key, 'value');
        $result = $this->cacheEngine->delete($key);
        $this->assertTrue($result);

        $result = $this->cacheEngine->has($key);
        $this->assertFalse($result);
    }

    public function testClear()
    {
        $this->cacheEngine->set('key1', 'value1');
        $this->cacheEngine->set('key2', 'value2');

        $result = $this->cacheEngine->clear();
        $this->assertTrue($result);

        $this->assertFalse($this->cacheEngine->has('key1'));
        $this->assertFalse($this->cacheEngine->has('key2'));
    }

    public function testGetMultiple()
    {
        $keys = ['key1', 'key2', 'key3'];
        $values = ['value1', 'value2', 'value3'];

        foreach ($keys as $index => $key) {
            $this->cacheEngine->set($key, $values[$index]);
        }

        $results = $this->cacheEngine->getMultiple($keys);
        $this->assertEquals(array_combine($keys, $values), ($results));
    }

    public function testSetMultiple()
    {
        $items = ['key1' => 'value1', 'key2' => 'value2'];
        $result = $this->cacheEngine->setMultiple($items);
        $this->assertTrue($result);

        foreach ($items as $key => $value) {
            $this->assertEquals($value, $this->cacheEngine->get($key));
        }
    }

    public function testDeleteMultiple()
    {
        $keys = ['key1', 'key2'];
        foreach ($keys as $key) {
            $this->cacheEngine->set($key, 'value');
        }

        $result = $this->cacheEngine->deleteMultiple($keys);
        $this->assertTrue($result);

        foreach ($keys as $key) {
            $this->assertFalse($this->cacheEngine->has($key));
        }
    }

    public function testHas()
    {
        $key = 'exists';
        $this->cacheEngine->set($key, 'yes');
        $this->assertTrue($this->cacheEngine->has($key));

        $this->cacheEngine->delete($key);
        $this->assertFalse($this->cacheEngine->has($key));
    }
}
