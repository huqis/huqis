<?php

namespace frame\library\executor;

use frame\library\TemplateContext;

/**
 * Template executor through the include function
 */
class IncludeTemplateExecutor extends AbstractTemplateExecutor {

    /**
     * Executes the provided compiled code
     * @param \frame\library\TemplateContext $context Runtime context of the
     * template
     * @param string $code Compiled template code
     * @param string $runtimeId Id of the compiled template function
     * @return string Rendered template
     */
    public function execute(TemplateContext $context, $code, $runtimeId) {
        $this->file = null;

        parent::execute($context, $code, $runtimeId);

        if ($this->file) {
            unlink($this->file);
        }
    }

    /**
     * Loads the compiled code of the template
     * @param string $code Compiled template code
     * @param string $runtimeId Id of the compiled template function
     * @return null
     */
    protected function loadCode($code, $runtimeId) {
        $this->file = tempnam(sys_get_temp_dir(), 'frame-' . $runtimeId . '-');

        file_put_contents($this->file, '<?php ' . $code);

        include($this->file);
    }

}
