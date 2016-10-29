<?php

namespace frame\library\cache;

use frame\library\helper\FileHelper;

use \Exception;

/**
 * Directory implementation of a template cache. Each cache item will be stored in
 * a file in the set directory.
 */
class DirectoryTemplateCache extends AbstractTemplateCache {

    /**
     * Extension for the cache files
     * @var string
     */
    const EXTENSION = '.php';

    /**
     * Path to the directory of the cache
     * @var string
     */
    private $directory;

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
    public function __construct($directory) {
        parent::__construct();

        $this->directory = rtrim($directory, '/');
        $this->items = [];
    }

    /**
     * Sets an item to this cache
     * @param \frame\library\cache\TemplateCacheItem $item
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

        $meta = $item->getMeta();

        $contents = "/*\n";
        foreach ($meta as $key => $value) {
            $contents .= $key . ': ' . $value . "\n";
        }
        $contents .= "*/\n";
        $contents .= $item->getValue();

        FileHelper::write($this->getFile($itemKey), $contents);

        $this->items[$itemKey] = $item;
    }

    /**
     * Gets an item from this pool
     * @param string $key Key of the cached item
     * @return \frame\library\cache\CacheItem Instance of the cached item
     */
    public function get($key) {
        if (isset($this->items[$key])) {
            return $this->items[$key];
        }

        $item = $this->create($key);

        $contents = FileHelper::read($this->getFile($key));
        if ($contents !== false) {
            $lines = explode("\n", $contents);
            do {
                $line = array_shift($lines);

                if ($line == '/*') {
                    continue;
                } elseif ($line == '*/') {
                    break;
                }

                list($metaName, $metaValue) = explode(': ', $line, 2);

                $item->setMeta($metaName, $metaValue);
            } while ($lines);

            $item->setValue(implode("\n", $lines));
        }

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
            $files = scandir($this->directory);
            if ($files === false) {
                return;
            }

            $files = array_diff($files, ['..', '.']);
            foreach ($files as $file) {
                FileHelper::delete($this->directory . '/' . $file);
            }

            $this->items = [];
        } else {
            $file = $this->getFile($key);
            if (file_exists($file)) {
                FileHelper::delete($file);
            }

            if (isset($this->items[$key])) {
                unset($this->items[$key]);
            }
        }
    }

    /**
     * Gets the file name for the provided key
     * @param string $key Key of the cache item
     * @return null
     */
    private function getFile($key) {
        return $this->directory . '/' . $key . self::EXTENSION;
    }

}
