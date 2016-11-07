<?php

namespace huqis\cache;

use huqis\helper\FileHelper;

use \PHPUnit_Framework_TestCase;

class DirectoryTemplateCacheTest extends AbstractTemplateCacheTest {

    private $directory;

    public function setUp() {
        $this->directory = __DIR__ . '/../../../../data/cache';
        $this->cache = new DirectoryTemplateCache($this->directory);
    }

    public function tearDown() {
        FileHelper::delete(__DIR__ . '/../../../../data');
        $this->cache = null;
    }

    public function testSetAndGet() {
        $key = 'item.key';
        $value = 'My cached value';

        // empty cache
        $this->assertFalse(file_exists($this->directory . '/' . $key . DirectoryTemplateCache::EXTENSION));

        // set a value to it
        $cacheItem = $this->cache->create($key);
        $cacheItem->setValue($value);
        $cacheItem->setMeta('meta', $value);

        $this->cache->set($cacheItem);

        $this->assertTrue(file_exists($this->directory . '/' . $key . DirectoryTemplateCache::EXTENSION));

        // read value from it
        $cacheItem = $this->cache->get($key);
        $this->assertTrue($cacheItem->isValid());
        $this->assertEquals($value, $cacheItem->getValue());
        $this->assertEquals($value, $cacheItem->getMeta('meta'));
    }

    public function testFlush() {
        $values = array(
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'key4' => 'value4',
        );

        // set a value to it
        foreach ($values as $key => $value) {
            $cacheItem = $this->cache->create($key);
            $cacheItem->setValue($value);

            $this->cache->set($cacheItem);
        }

        // flush single value from it
        reset($values);
        $flushKey = current($values);

        $this->cache->flush($flushKey);

        // test flushed value
        foreach ($values as $key => $value) {
            if ($key == $flushKey) {
                $this->assertFalse($this->cache->get($key)->isValid(), $key);
                $this->assertFalse(file_exists($this->directory . '/' . $key . DirectoryTemplateCache::EXTENSION), $key);
            } else {
                $this->assertTrue($this->cache->get($key)->isValid(), $key);
                $this->assertTrue(file_exists($this->directory . '/' . $key . DirectoryTemplateCache::EXTENSION), $key);
            }
        }

        // flush full cache
        $this->cache->flush();

        foreach ($values as $key => $value) {
            $this->assertFalse($this->cache->get($key)->isValid(), $key);
            $this->assertFalse(file_exists($this->directory . '/' . $key . DirectoryTemplateCache::EXTENSION), $key);
        }

        $this->assertEquals(array('.', '..'), scandir($this->directory));
    }

}
