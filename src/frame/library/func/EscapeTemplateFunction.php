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

        $value = '';
        $format = 'html';

        foreach ($arguments as $index => $argument) {
            switch ($index) {
                case 0:
                    $value = $argument;

                    break;
                case 1:
                    $format = $argument;

                    break;
                default:
                    throw new RuntimeTemplateException('Could not call escape: invalid argument count');
            }
        }

        switch ($format) {
            case 'html':
                $result = htmlspecialchars($value, ENT_QUOTES);

                break;
            case 'url':
                if (is_array($value)) {
                    $result = http_build_query($value);
                } else {
                    $result = rawurlencode($value);
                }

                break;
            case 'tags':
                $result = strip_tags($value);

                break;
            case 'slug':
                $result = StringHelper::safeString($value);

                break;
            default:
                throw new RuntimeTemplateException('Could not call escape: ' . $format . ' is not a valid escape format (html, url, safe)');
        }

        return $result;
    }

}
