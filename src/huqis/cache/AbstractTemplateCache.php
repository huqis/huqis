<?php

namespace huqis\cache;

/**
 * Abstract implementation for a template cache
 */
abstract class AbstractTemplateCache implements TemplateCache {

    /**
     * Empty cache item to clone for a new cache item
     * @var TemplateCacheItem
     */
    protected $emptyCacheItem;

    /**
     * Constructs a new abstract cache pool
     * @param \huqis\cache\CacheItem $emptyCacheItem Empty cache item to
     * clone for a new cache item
     * @return null
     */
    public function __construct(CacheItem $emptyCacheItem = null) {
        if (!$emptyCacheItem) {
            $emptyCacheItem = new TemplateCacheItem();
        }

        $this->emptyCacheItem = $emptyCacheItem;
    }

    /**
     * Creates a item for this cache
     * @param string $key Key of the cached item
     * @return \huqis\cache\TemplateCacheItem New instance of a cache
     * item for the provided key
     */
    public function create($key) {
        $cacheItem = clone $this->emptyCacheItem;
        $cacheItem->setKey($key);

        return $cacheItem;
    }

}
