<?php

namespace frame\library\func;

use frame\library\exception\RuntimeTemplateException;
use frame\library\TemplateContext;

/**
 * Use a default value when the provided value is empty
 *
 * Syntax: default(<value>, <default>)
 *
 * {$result = default($string, "Defaults to this")}
 * {$result = $string|default("Defaults to this")}
 */
class DefaultTemplateFunction implements TemplateFunction {

    /**
     * Calls the function with the provided context and arguments
     * @param \frame\library\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments) {
        if (count($arguments) !== 2) {
            throw new RuntimeTemplateException('Could not call default: invalid argument count');
        }

        if ($arguments[0] === null || $arguments[0] === '') {
            return $arguments[1];
        }

        return $arguments[0];
    }

}
