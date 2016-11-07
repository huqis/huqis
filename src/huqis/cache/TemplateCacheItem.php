<?php

namespace huqis\cache;

use huqis\exception\CacheTemplateException;

/**
 * Data container of the cache item
 */
class TemplateCacheItem {

    /**
     * Key of this item
     * @var string
     */
    protected $key;

    /**
     * Value to cache
     * @var mixed
     */
    protected $value;

    /**
     * Meta data for this item
     * @var array
     */
    protected $meta;

    /**
     * Constructs a new cached item
     * @return null
     */
    public function __construct() {
        $this->key = null;
        $this->value = null;
        $this->meta = [];

        $this->isValueUnset = true;
    }

    /**
     * Sets the key to store the value under
     * @param string $key Key of the cache value
     * @return null
     */
    public function setKey($key) {
        if (!is_string($key) || $key == '') {
            throw new CacheTemplateException('Could not set the key of the cache item: provided key is invalid or empty');
        }

        $this->key = $key;
    }

    /**
     * Gets the key of this item
     * @return string Key of the cache value
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * Sets the cached value
     * @param mixed $value Value to store in the cache
     * @return null
     */
    public function setValue($value) {
        $this->value = $value;

        unset($this->isValueUnset);
    }

    /**
     * Gets the cached value
     * @return mixed Value stored in the cache
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * Sets a meta value
     * @param string $key Key of the meta value
     * @param mixed $value Meta value
     * @return null
     */
    public function setMeta($key, $value = null) {
        if (!is_string($key) || $key == '') {
            throw new CacheTemplateException('Could not set meta of the cache item: provided key is invalid or empty');
        }

        if ($value !== null) {
            $this->meta[$key] = $value;
        } elseif (isset($this->meta[$key])) {
            unset($this->meta[$key]);
        }
    }

    /**
     * Gets a meta value
     * @param string|null $key Key of the meta value
     * @param mixed $default Default value for when the key is not set
     * @return mixed Value for the key if provided, all meta of no arguments
     * are provided
     */
    public function getMeta($key = null, $default = null) {
        if ($key === null) {
            return $this->meta;
        } elseif (!is_string($key) || $key == '') {
            throw new CacheTemplateException('Could not get meta of the cache item: provided key is invalid or empty');
        } elseif (isset($this->meta[$key])) {
            return $this->meta[$key];
        } else {
            return $default;
        }
    }

    /**
     * Checks if this cache item is valid to use
     * @return boolean True if valid, false otherwise
     */
    public function isValid() {
        if ($this->key === null || isset($this->isValueUnset)) {
            return false;
        }

        return true;
    }

}
