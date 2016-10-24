<?php

namespace frame\library\func;

use frame\library\TemplateContext;

/**
 * Interface for a function which can be accessed from a template context
 * @see \frame\library\TemplateContext
 */
interface TemplateFunction {

    /**
     * Calls the function with the provided context and arguments
     * @param \frame\library\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments);

}
