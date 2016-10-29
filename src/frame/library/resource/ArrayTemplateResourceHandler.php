<?php

namespace frame\library\resource;

use frame\library\exception\NotFoundTemplateException;

/**
 * Template resource handler with an array as template container
 */
class ArrayTemplateResourceHandler extends AbstractTemplateResourceHandler {

    /**
     * Resources held by this handler. An array with the resource name as key
     * and the contents of the template as value
     * @var array
     */
    private $resources = [];

    /**
     * Adds or updates a resource in this handler
     * @param string $name Name of the template resource
     * @param string $template Contents of the template
     * @return null
     */
    public function setResource($name, $template) {
        $this->resources[$name] = $template;
    }

    /**
     * Sets multiple resources at once
     * @param array $resources Array with the name of the template resource as
     * key and the contents of the template as value
     * @return null
     */
    public function setResources(array $resources) {
        foreach ($resources as $name => $template) {
            $this->setResource($name, $template);
        }
    }

    /**
     * Removes a single resource from this handler
     * @param string $name Name of the template resource
     * @return boolean True when found and removed, false otherwise
     */
    public function unsetResource($name) {
        if (!isset($this->resources[$name])) {
            return false;
        }

        unset($this->resources[$name]);

        return true;
    }

    /**
     * Removes all resources in this handler
     * @return null
     */
    public function flushResources() {
        $this->resources = [];
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
        $this->testResource($name);

        $this->requestedResources[$name] = true;

        return $this->resources[$name];
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
        $this->testResource($name);

        return null;
    }

    /**
     * Checks if the requested resource is available
     * @return null When the resource is available
     * @throws \frame\library\exception\NotFoundTemplateException when the
     * resource does not exist
     */
    private function testResource($name) {
        if (isset($this->resources[$name])) {
            return;
        }

        $exception = new NotFoundTemplateException('Could not find template resource: ' . $name . ' does not exist');
        $exception->setResource($name);

        throw $exception;
    }

}
