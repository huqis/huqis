<?php

namespace huqis\cache;

use PHPUnit\Framework\TestCase;

class TemplateCacheItemTest extends TestCase {

    public function testKeyAndValue() {
        $key = 'test';
        $value = 15;

        $cacheItem = new TemplateCacheItem();

        $this->assertNull($cacheItem->getKey());
        $this->assertNull($cacheItem->getValue());

        $cacheItem->setKey($key);
        $cacheItem->setValue($value);

        $this->assertEquals($key, $cacheItem->getKey());
        $this->assertEquals($value, $cacheItem->getValue());
    }

    public function testMeta() {
        $key = 'test';
        $value = 15;

        $cacheItem = new TemplateCacheItem();
        $cacheItem->setMeta($key, $value);

        $this->assertEquals($value, $cacheItem->getMeta($key));
        $this->assertNull($cacheItem->getMeta('unexistant'));
        $this->assertEquals($value, $cacheItem->getMeta('unexistant', $value));
        $this->assertEquals(array($key => $value), $cacheItem->getMeta());
    }

    public function testIsValid() {
        $cacheItem = new TemplateCacheItem();

        $this->assertFalse($cacheItem->isValid());

        $cacheItem->setKey('test');

        $this->assertFalse($cacheItem->isValid());

        $cacheItem->setMeta('meta', true);

        $this->assertFalse($cacheItem->isValid());

        $cacheItem->setValue('value');

        $this->assertTrue($cacheItem->isValid());
    }

    /**
     * @dataProvider providerExceptionOnInvalidKey
     * @expectedException huqis\exception\CacheTemplateException
     */
    public function testSetKeyThrowsExceptionOnInvalidKey($key) {
        $cacheItem = new TemplateCacheItem();
        $cacheItem->setKey($key);
    }

    /**
     * @dataProvider providerExceptionOnInvalidKey
     * @expectedException huqis\exception\CacheTemplateException
     */
    public function testSetMetaThrowsExceptionOnInvalidKey($key) {
        $cacheItem = new TemplateCacheItem();
        $cacheItem->setMeta($key, 15);
    }

    /**
     * @dataProvider providerExceptionOnInvalidKeyWithoutNull
     * @expectedException huqis\exception\CacheTemplateException
     */
    public function testGetMetaThrowsExceptionOnInvalidKey($key) {
        $cacheItem = new TemplateCacheItem();
        $cacheItem->getMeta($key);
    }

    public function providerExceptionOnInvalidKey() {
        return array(
            array(null),
            array(''),
            array($this),
            array(array()),
        );
    }

    public function providerExceptionOnInvalidKeyWithoutNull() {
        $data = $this->providerExceptionOnInvalidKey();

        array_shift($data);

        return $data;
    }

}
