<?php

namespace huqis\func;

use huqis\TemplateContext;

/**
 * Function to concat multiple values together
 *
 * Syntax: concat(<value>, [<value-2>, [<value-3>, [...]]])
 *
 * {$result = concat("Hello ", $name, "!")}
 */
class ConcatTemplateFunction implements TemplateFunction {

    /**
     * Calls the function with the provided context and arguments
     * @param \huqis\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments) {
        $output = '';

        foreach ($arguments as $argument) {
            $output .= $argument;
        }

        return $output;
    }

}
