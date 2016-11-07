<?php

namespace huqis\func;

use huqis\exception\RuntimeTemplateException;
use huqis\TemplateContext;

/**
 * String trim function
 *
 * Syntax: trim([$string[, $characters])
 *
 * {$result = trim($string)}
 * {$result = trim($string, "-")}
 * {$result = $string|trim}
 * {$result = $string|trim("-")}
 */
class TrimTemplateFunction implements TemplateFunction {

    /**
     * Calls the function with the provided context and arguments
     * @param \huqis\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments) {
        $string = '';
        $characters = " \t\n\r\0\x0B";

        foreach ($arguments as $index => $argument) {
            switch ($index) {
                case 0:
                    $string = $argument;

                    break;
                case 1:
                    $characters = $argument;

                    break;
                default:
                    throw new RuntimeTemplateException('Could not call trim: invalid argument count');
            }
        }

        return trim($string, $characters);
    }

}
