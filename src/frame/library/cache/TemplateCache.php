<?php

namespace frame\library\cache;

/**
 * Interface for the template cache.
 */
interface TemplateCache {

    /**
     * Creates a item for this pool
     * @param string $key Key of the cached item
     * @ireturn \frame\library\cache\TemplateCacheItem New instance of a cache
     * item for the provided key
     */
    public function create($key);

    /**
     * Sets an item to this cache
     * @param \frame\library\cache\TemplateCacheItem $item
     * @return null
     */
    public function set(TemplateCacheItem $item);

    /**
     * Gets an item from this cache
     * @param string $key Key of the cached item
     * @return \frame\library\cache\TemplateCacheItem Instance of the cached
     * item
     */
    public function get($key);

    /**
     * Flushes this cache or a single key from it
     * @param string $key Provide a key to remove a single cached item
     * @return null
     */
    public function flush($key = null);

}
