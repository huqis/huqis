<?php

namespace frame\library\executor;

use frame\library\exception\RuntimeTemplateException;

/**
 * Template executor through the eval function
 */
class EvalTemplateExecutor extends AbstractTemplateExecutor {

    /**
     * Loads the compiled code of the template
     * @param string $code Compiled template code
     * @param string $runtimeId Id of the compiled template function
     * @return null
     */
    protected function loadCode($code, $runtimeId) {
        $result = eval($code);
        if ($result !== false) {
            return;
        }

        $error = error_get_last();

        throw new RuntimeTemplateException($error['message'] . ' on line ' . $error['line']);
    }

}
