<?php

namespace huqis\func;

use huqis\exception\RuntimeTemplateException;
use huqis\helper\StringHelper;
use huqis\TemplateContext;

/**
 * String capitalze function
 *
 * Syntax: capitalize(<string>)
 *
 * {$result = capitalize($string)}
 * {$result = $string|capitalize}
 */
class CapitalizeTemplateFunction implements TemplateFunction {

    /**
     * Calls the function with the provided context and arguments
     * @param \huqis\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments) {
        $string = '';
        $type = 'all';

        foreach ($arguments as $index => $argument) {
            switch ($index) {
                case 0:
                    $string = $argument;

                    break;
                case 1:
                    $type = $argument;

                    break;
                default:
                    throw new RuntimeTemplateException('Could not call capitalize: invalid argument count');
            }
        }

        switch ($type) {
            case 'all':
                return ucwords($string);
            case 'first':
                return ucfirst($string);
            default:
                throw new RuntimeTemplateException('Could not call capitalize: ' . $type . ' is not a valid capitalize type (all, first)');
        }
    }

}
