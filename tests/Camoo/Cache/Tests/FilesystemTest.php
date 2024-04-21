<?php

declare(strict_types=1);

namespace Camoo\Cache\Tests;

use Camoo\Cache\Filesystem;
use PHPUnit\Framework\TestCase;

class FilesystemTest extends TestCase
{
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $options = [
            'namespace' => 'test',
            'directory' => '/tmp/cache', // Ensure this directory is writable
        ];
        $this->filesystem = new Filesystem($options);
    }

    protected function tearDown(): void
    {
        $this->filesystem->clear();
    }

    public function testSetAndGet()
    {
        $key = 'unique_key';
        $value = 'Test Value';
        $this->assertTrue($this->filesystem->set($key, $value));
        $this->assertEquals($value, $this->filesystem->get($key));
        $this->assertNull($this->filesystem->get('non_existing_key'));
    }

    public function testDelete()
    {
        $key = 'to_delete';
        $this->filesystem->set($key, 'value');
        $this->assertTrue($this->filesystem->delete($key));
        $this->assertNull($this->filesystem->get($key));
    }

    public function testClear()
    {
        $this->filesystem->set('key1', 'value1');
        $this->filesystem->set('key2', 'value2');
        $this->assertTrue($this->filesystem->clear());
        $this->assertNull($this->filesystem->get('key1'));
        $this->assertNull($this->filesystem->get('key2'));
    }

    public function testGetMultiple()
    {
        $this->filesystem->set('key1', 'value1');
        $this->filesystem->set('key2', 'value2');
        $result = $this->filesystem->getMultiple(['key1', 'key2', 'key3'], 'default');
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'default'], $result);
    }

    public function testSetMultiple()
    {
        $values = ['key1' => 'value1', 'key2' => 'value2'];
        $this->assertTrue($this->filesystem->setMultiple($values));
        $this->assertEquals('value1', $this->filesystem->get('key1'));
        $this->assertEquals('value2', $this->filesystem->get('key2'));
    }

    public function testDeleteMultiple()
    {
        $this->filesystem->set('key1', 'value1');
        $this->filesystem->set('key2', 'value2');
        $this->assertTrue($this->filesystem->deleteMultiple(['key1', 'key2']));
        $this->assertNull($this->filesystem->get('key1'));
        $this->assertNull($this->filesystem->get('key2'));
    }

    public function testHas()
    {
        $key = 'existing_key';
        $this->filesystem->set($key, 'value');
        $this->assertTrue($this->filesystem->has($key));
        $this->filesystem->delete($key);
        $this->assertFalse($this->filesystem->has($key));
    }
}
