<?php

namespace huqis\exception;

/**
 * Abstract template exception exception which can hold a resource
 */
abstract class AbstractResourceTemplateException extends TemplateException {

    /**
     * Name of the template resource
     * @var string
     */
    private $resource;

    /**
     * Sets the requested template resource
     * @param string $resource Name of the template resource
     * @return null
     */
    public function setResource($resource) {
        $this->resource = $resource;
    }

    /**
     * Gets the requested template resource
     * @return string Name of the template resource
     */
    public function getResource() {
        return $this->resource;
    }

}
