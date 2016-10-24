<?php

namespace frame\library\func;

use frame\library\exception\RuntimeTemplateException;
use frame\library\helper\StringHelper;
use frame\library\TemplateContext;

/**
 * String escape function
 *
 * Syntax: escape([<string>, [<type>]])
 *
 * {$result = escape($string)}
 * {$result = escape($string, "html")}
 * {$result = $string|escape}
 * {$result = $string|escape:"url"}
  */
class EscapeTemplateFunction implements TemplateFunction {

    /**
     * Calls the function with the provided context and arguments
     * @param \frame\library\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments) {
        if (!$arguments) {
            throw new RuntimeTemplateException('Could not call escape: invalid argument count');
        }

        $string = '';
        $type = 'html';

        foreach ($arguments as $index => $argument) {
            switch ($index) {
                case 0:
                    $string = $argument;

                    break;
                case 1:
                    $type = $argument;

                    break;
                default:
                    throw new RuntimeTemplateException('Could not call escape: invalid argument count');
            }
        }

        switch ($type) {
            case 'html':
                $result = htmlspecialchars($string, ENT_QUOTES);

                break;
            case 'url':
                $result = rawurlencode($string);

                break;
            case 'safe':
                $result = StringHelper::safeString($string);

                break;
            default:
                throw new RuntimeTemplateException('Could not call escape: ' . $type . ' is not a valid escape type (html, url, safe)');
        }

        return $result;
    }

}
