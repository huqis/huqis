<?php

namespace huqis\func;

use huqis\exception\RuntimeTemplateException;
use huqis\TemplateContext;

/**
 * String format function
 *
 * Syntax: format($value, $format[, $extra]])
 *
 * {$result = format($string, "date")}
 * {$result = $string|format("date")}
  */
class FormatTemplateFunction implements TemplateFunction {

    /**
     * Calls the function with the provided context and arguments
     * @param \huqis\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments) {
        if (!$arguments) {
            throw new RuntimeTemplateException('Could not call format: invalid argument count');
        }

        $value = '';
        $format = null;
        $extra = null;

        foreach ($arguments as $index => $argument) {
            switch ($index) {
                case 0:
                    $value = $argument;

                    break;
                case 1:
                    $format = $argument;

                    break;
                case 2:
                    $extra = $argument;

                    break;
                default:
                    throw new RuntimeTemplateException('Could not call format: invalid argument count');
            }
        }

        switch ($format) {
            case 'date':
                if ($extra === null) {
                    $extra = 'c';
                }

                $result = date($extra, $value);

                break;
            case 'number':
                if ($extra === null) {
                    $extra = 2;
                }

                $result = number_format($value, $extra);

                break;
            case 'json':
                if ($extra !== null) {
                    throw new RuntimeTemplateException('Could not call format: ' . $format . ' does not take any extra arguments');
                }

                $result = json_encode($value, JSON_PRETTY_PRINT);

                break;
            default:
                throw new RuntimeTemplateException('Could not call format: ' . $format . ' is not a valid format (date, number, json)');
        }

        return $result;
    }

}
