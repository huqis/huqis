<?php

namespace frame\library\resource;

use frame\library\exception\TemplateException;

/**
 * Template resource handler which chains or combines different template
 * resource handlers to use them alongside each other
 */
class ChainTemplateResourceHandler extends AbstractTemplateResourceHandler {

    /**
     * Resource handlers held by this handler. An array with the name as key
     * and the resource handler as value. The shackles of the chain.
     * @var array
     */
    private $resourceHandlers = [];

    /**
     * Name of the default resource handler
     * @var string
     */
    private $defaultResourceHandler;

    /**
     * Separator between the shackle name and the resource name for the shackle
     * resource handler
     * @var string
     */
    private $separator = ':';

    /**
     * Adds or updates a resource handler
     * @param string $name Name of the resource handler
     * @param TemplateResourceHandler $resourceHandler Instance of a resource
     * handler
     * @return null
     * @throws \frame\library\exception\TemplateException when the provided name
     * is invalid
     */
    public function setResourceHandler($name, TemplateResourceHandler $resourceHandler) {
        if (!is_string($name) || $name == '') {
            throw new TemplateException('Could not set the resource handler: name should be a non-empty string');
        }

        $this->resourceHandlers[$name] = $resourceHandler;

        if ($this->defaultResourceHandler === null) {
            $this->defaultResourceHandler = $name;
        }
    }

    /**
     * Sets multiple resources handlers at once
     * @param array $resourceHandlers Array with the name of the resource
     * handler as key and the instance as value
     * @return null
     */
    public function setResourceHandlers(array $resourceHandlers) {
        foreach ($resourceHandlers as $name => $resourceHandler) {
            $this->setResourceHandler($name, $resourceHandler);
        }
    }

    /**
     * Gets a resource handler from the chain
     * @param string $name Name of the resource handler
     * @return TemplateResourceHandler Instance of the resource handler
     * @throws \frame\library\exception\TemplateException when the resource
     * handler is not registered in this chain
     */
    public function getResourceHandler($name) {
        if (!isset($this->resourceHandlers[$name])) {
            throw new TemplateException('Could not get the resource handler: ' . $name . ' is not registered in this chain');
        }

        return $this->resourceHandlers[$name];
    }

    /**
     * Gets all the registered resource handlers
     * @return array Array with the name of the resource handler as key and the
     * instance as value
     */
    public function getResourceHandlers() {
        return $this->resourceHandlers;
    }

    /**
     * Removes a resource handler from the chain
     * @param string $name Name of the resource handler
     * @return boolean True when found and removed, false otherwise
     */
    public function unsetResourceHandler($name) {
        if (!isset($this->resourceHandlers[$name])) {
            return false;
        }

        unset($this->resourceHandlers[$name]);

        return true;
    }

    /**
     * Sets the default resource handler
     * @param string $name Name of the new default resource handler
     * @return null
     * @throws \frame\library\exception\TemplateException when the resource
     * handler is not registered
     */
    public function setDefaultResourceHandler($name) {
        if (!isset($this->resourceHandlers[$name])) {
            throw new TemplateException('Could not set the default resource handler: ' . $name . ' is not registered in this chain');
        }

        $this->defaultResourceHandler = $name;
    }

    /**
     * Gets the default resource handler
     * @return string Name of the default resource handler
     */
    public function getDefaultResourceHandler() {
        return $this->defaultResourceHandler;
    }

    /**
     * Sets the separator between the name of the resource handler and the
     * template resource name when requesting a template from a specific
     * resource handler.
     * eg array:my-template
     * @param string $separator Separator string
     * @return null
     */
    public function setSeparator($separator) {
        $this->separator = $separator;
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
        list($shackle, $resource) = $this->getShackleFromResource($name);

        $resourceHandler = $this->getResourceHandler($shackle);

        $template = $resourceHandler->getResource($resource);

        $this->requestedResources[$name] = true;

        return $template;
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
        list($shackle, $resource) = $this->getShackleFromResource($name);

        $resourceHandler = $this->getResourceHandler($shackle);

        return $resourceHandler->getModificationTime($resource);
    }

    /**
     * Gets the name of the resource handler from the requested template name.
     * Valid entries are a resource name for the default resource handler or for
     * a specific shackle by prefixing the resource name with the name of the
     * resource handler and the separator
     * @param string $name Name of the template resource. When a specific
     * shackle is requested, the prefix will be removed from the provided name.
     * @return Name of the resource handler
     */
    private function getShackleFromResource($name) {
        $positionSeparator = strpos($name, $this->separator);
        if ($positionSeparator === false) {
            $shackle = $this->defaultResourceHandler;
        } else {
            $shackle = substr($name, 0, $positionSeparator);
            $name = substr($name, $positionSeparator + 1);
        }

        return [$shackle, $name];
    }

}
