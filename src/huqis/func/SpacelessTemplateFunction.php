<?php

namespace huqis\func;

use huqis\exception\RuntimeTemplateException;
use huqis\TemplateContext;

/**
 * Removes whitespace between HTML tags.
 *
 * Syntax: spaceless(<string>)
 *
 * {$result = spaceless($string)}
 * {$result = $string|spaceless}
 */
class SpacelessTemplateFunction implements TemplateFunction {

    /**
     * Calls the function with the provided context and arguments
     * @param \huqis\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments) {
        if (count($arguments) !== 1) {
            throw new RuntimeTemplateException('Could not call spaceless: invalid argument count');
        }

        return trim(preg_replace('/>\s+</', '><', $arguments[0]));
    }

}
