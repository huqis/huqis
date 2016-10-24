<?php

namespace frame\library\resource;

use frame\library\exception\NotFoundTemplateException;
use frame\library\exception\TemplateException;

/**
 * Template resource handler with a directory as template container and files as
 * template resources
 */
class DirectoryTemplateResourceHandler extends ArrayTemplateResourceHandler {

    /**
     * Path of the template directory
     * @var string
     */
    private $directory;

    /**
     * Constructs a new directory template resource handler
     * @var string $directory Path of the template directory
     * @return null
     */
    public function __construct($directory) {
        $this->setDirectory($directory);
    }

    /**
     * Sets the template directory
     * @param string $directory Path to the template directory
     * @return null
     * @throws \frame\library\exception\TemplateException when the provided
     * directory is an invalid value
     */
    public function setDirectory($directory) {
        if (!is_string($directory) || $directory == '') {
            throw new TemplateException('Could not set the directory: directory should be a non-empty string');
        }

        // remove trailing /
        $this->directory = rtrim($directory, '/');
    }

    /**
     * Gets the template directory
     * @return string Path to the template directory
     */
    public function getDirectory() {
        return $this->directory;
    }

    /**
     * Gets a resource by the provided name
     * @param string $name Name of a template resource. This is the name which
     * is passed as resource to the template engine and used in extends and
     * include blocks
     * @return string Contents of the template.
     * @throws \frame\library\exception\NotFoundTemplateException when the
     * resource does not exist
     */
    public function getResource($name) {
        $fileName = $this->getFileName($name);

        $contents = @file_get_contents($fileName);
        if ($contents !== false) {
            return $contents;
        }

        $exception = new NotFoundTemplateException('Could not find template resource: ' . $name . ' does not exist or is not readable');
        $exception->setResource($name);

        throw $exception;
    }

    /**
     * Gets the modification time of the provided resource
     * @param string $name Name of the template resource as used in getResource
     * @return integer|null Timestamp of the modification time or null when
     * unknown
     * @throws \frame\library\exception\NotFoundTemplateException when the
     * resource does not exist
     * @see getResource
     */
    public function getModificationTime($name) {
        $fileName = $this->getFileName($name);

        if (!file_exists($fileName)) {
            $exception = new NotFoundTemplateException('Could not find template resource: ' . $name . ' does not exist');
            $exception->setResource($name);

            throw $exception;
        }

        $modificationTime = @filemtime($fileName);
        if ($modificationTime === false) {
            return null;
        }

        return $modificationTime;
    }

    /**
     * Gets the file name for the requested resource name
     * @param string $name Requested resource name
     * @return string File name of the requested resource
     */
    protected function getFileName($name) {
        // @TODO check to only make template accessible which are in the
        // template directory
        return $this->directory . '/' . $name;
    }

}
