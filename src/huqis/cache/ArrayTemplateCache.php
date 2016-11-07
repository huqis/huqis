<?php

namespace huqis\cache;

use \Exception;

/**
 * Array implementation of a template cache. Each cache item is stored in an
 * array and kept during the request
 */
class ArrayTemplateCache extends AbstractTemplateCache {

    /**
     * Requested cache items which are already retrieved from the file system
     * @var array
     */
    private $items;

    /**
     * Constructs a new directory cache
     * @param string $directory Path to the directory of this cache
     * @return null
     */
    public function __construct() {
        parent::__construct();

        $this->items = [];
    }

    /**
     * Sets an item to this cache
     * @param \huqis\cache\TemplateCacheItem $item
     * @return null
     */
    public function set(TemplateCacheItem $item) {
        $itemKey = $item->getKey();

        if (!$item->isValid()) {
            // not a valid item, don't store
            if (isset($this->items[$itemKey])) {
                unset($this->items[$itemKey]);
            }

            return;
        }

        $this->items[$itemKey] = $item;
    }

    /**
     * Gets an item from this pool
     * @param string $key Key of the cached item
     * @return \huqis\cache\CacheItem Instance of the cached item
     */
    public function get($key) {
        if (isset($this->items[$key])) {
            return $this->items[$key];
        }

        $item = $this->create($key);

        return $item;
    }

    /**
     * Flushes this pool
     * @param string $key Provide a key to only remove the cached item of that
     * key
     * @return null
     */
    public function flush($key = null) {
        if ($key === null) {
            $this->items = [];
        } else {
            if (isset($this->items[$key])) {
                unset($this->items[$key]);
            }
        }
    }

}
