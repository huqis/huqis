<?php

namespace huqis\cache;

use \PHPUnit_Framework_TestCase;

abstract class AbstractTemplateCacheTest extends PHPUnit_Framework_TestCase {

    protected $cache;

    public function testCreate() {
        $key = 'cache.key';
        $item = $this->cache->create($key);

        $this->assertNotNull($item);
        $this->assertTrue($item instanceof TemplateCacheItem);
        $this->assertEquals($key, $item->getKey());
    }

}
