<?php

namespace huqis\func;

use huqis\exception\RuntimeTemplateException;
use huqis\TemplateContext;

/**
 * Transform a string to upper case characters.
 *
 * Syntax: upper(<string>)
 *
 * {$result = upper($string)}
 * {$result = $string|upper}
 */
class UpperTemplateFunction implements TemplateFunction {

    /**
     * Calls the function with the provided context and arguments
     * @param \huqis\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments) {
        if (count($arguments) !== 1) {
            throw new RuntimeTemplateException('Could not call lower: invalid argument count');
        }

        return strtoupper($arguments[0]);
    }

}
