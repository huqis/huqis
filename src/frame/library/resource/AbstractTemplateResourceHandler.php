<?php

namespace frame\library\resource;

use frame\library\exception\NotFoundTemplateException;

/**
 * Template resource handler with an array as template container
 */
abstract class AbstractTemplateResourceHandler implements TemplateResourceHandler {

    /**
     * Resources requested on this handler. An array with the resource name as
     * key and true as value
     * @var array
     */
    protected $requestedResources = [];

    /**
     * Gets and resets the requested resources
     * @return array Array with a template resource name as key
     */
    public function getRequestedResources() {
        $requestedResources = $this->requestedResources;

        $this->requestedResources = [];

        return $requestedResources;
    }

}
