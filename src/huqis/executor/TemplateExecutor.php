<?php

namespace huqis\executor;

use huqis\TemplateContext;

/**
 * Interface for the executor of a compiled template
 */
interface TemplateExecutor {

    /**
     * Executes the provided compiled code
     * @param \huqis\TemplateContext $context Runtime context of the
     * template
     * @param string $code Compiled template code
     * @param string $runtimeId Id of the compiled template function
     * @return string Rendered template
     */
    public function execute(TemplateContext $context, $code, $runtimeId);

}
