<?php

namespace frame\library\func;

use frame\library\exception\RuntimeTemplateException;
use frame\library\helper\StringHelper;
use frame\library\TemplateContext;

/**
 * String truncate function
 *
 * Syntax: truncate([<string>, [<length>, [<etc>, [<breakwords>]]]])
 *
 * {$result = truncate($string)}
 * {$result = truncate($string, 80, "...", false)}
 * {$result = $string|truncate:50:"..."}
 */
class TruncateTemplateFunction implements TemplateFunction {

    /**
     * Calls the function with the provided context and arguments
     * @param \frame\library\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments) {
        $string = '';
        $length = 80;
        $etc = '...';
        $breakWords = false;

        foreach ($arguments as $index => $argument) {
            switch ($index) {
                case 0:
                    $string = $argument;

                    break;
                case 1:
                    $length = $argument;

                    break;
                case 2:
                    $etc = $argument;

                    break;
                case 3:
                    $breakWords = $argument;

                    break;
                default:
                    throw new RuntimeTemplateException('Could not call truncate: invalid argument count');
            }
        }

        return StringHelper::truncate($string, $length, $etc, $breakWords);
    }

}
