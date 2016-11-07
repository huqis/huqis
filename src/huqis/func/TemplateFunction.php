<?php

namespace huqis\func;

use huqis\TemplateContext;

/**
 * Interface for a function which can be accessed from a template context
 * @see \huqis\TemplateContext
 */
interface TemplateFunction {

    /**
     * Calls the function with the provided context and arguments
     * @param \huqis\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments);

}
