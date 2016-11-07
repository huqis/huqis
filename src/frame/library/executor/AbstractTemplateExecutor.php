<?php

namespace frame\library\executor;

use frame\library\TemplateContext;

/**
 * Abstract implementation for a executor of compiled templates
 */
abstract class AbstractTemplateExecutor implements TemplateExecutor {

    /**
     * History of loaded code indexed by runtimeId
     * @var array
     */
    private $loadedCode = [];

    /**
     * Executes the provided compiled code
     * @param \frame\library\TemplateContext $context Runtime context of the
     * template
     * @param string $code Compiled template code
     * @param string $runtimeId Id of the compiled template function
     * @return string Rendered template
     */
    public function execute(TemplateContext $context, $code, $runtimeId) {
        if (!isset($this->loadedCode[$runtimeId])) {
            $this->loadCode($code, $runtimeId);

            $this->loadedCode[$runtimeId] = true;
        }

        $this->executeTemplateFunction($context, $runtimeId);
    }

    /**
     * Loads the compiled code of the template
     * @param string $code Compiled template code
     * @param string $runtimeId Id of the compiled template function
     * @return null
     */
    abstract protected function loadCode($code, $runtimeId);

    /**
     * Executes the function of the compiled code
     * @param TemplateContext $context Runtime context of the template
     * @param string $runtimeId Id of the compiled template function
     * @return null
     */
    protected function executeTemplateFunction(TemplateContext $context, $runtimeId) {
        $template = 'frameTemplate' . $runtimeId;

        $template = new $template();
        $template->render($context);
    }

}
