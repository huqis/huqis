<?php

namespace frame\library\func;

use frame\library\exception\RuntimeTemplateException;
use frame\library\TemplateContext;

/**
 * String replace function, can be used as a modifier
 *
 * Syntax: replace(<string>, <search>, <replace>)
 *
 * {$result = replace($string, "search", "replace")}
 * {$result = $string|replace:"search":"replace"}
 */
class ReplaceTemplateFunction implements TemplateFunction {

    /**
     * Calls the function with the provided context and arguments
     * @param \frame\library\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments) {
        if (count($arguments) !== 3) {
            throw new RuntimeTemplateException('Could not call replace: invalid argument count');
        }

        return str_replace($arguments[1], $arguments[2], $arguments[0]);
    }

}
