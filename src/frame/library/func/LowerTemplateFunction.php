<?php

namespace frame\library\func;

use frame\library\exception\RuntimeTemplateException;
use frame\library\TemplateContext;

/**
 * Transform a string to lower case characters.
 *
 * Syntax: lower(<string>)
 *
 * {$result = lower($string)}
 * {$result = $string|lower}
 */
class LowerTemplateFunction implements TemplateFunction {

    /**
     * Calls the function with the provided context and arguments
     * @param \frame\library\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments) {
        if (count($arguments) !== 1) {
            throw new RuntimeTemplateException('Could not call lower: invalid argument count');
        }

        return strtolower($arguments[0]);
    }

}
