<?php

namespace frame\library\executor;

use frame\library\TemplateContext;

/**
 * Template executor through the include function
 */
class IncludeTemplateExecutor extends AbstractTemplateExecutor {

    /**
     * Stack of paths to the temporary files with the compiled template code
     * @var array
     */
    private $files = [];

    /**
     * Executes the provided compiled code
     * @param \frame\library\TemplateContext $context Runtime context of the
     * template
     * @param string $code Compiled template code
     * @param string $runtimeId Id of the compiled template function
     * @return string Rendered template
     */
    public function execute(TemplateContext $context, $code, $runtimeId) {
        $this->files[] = null;

        parent::execute($context, $code, $runtimeId);

        $file = array_pop($this->files);
        if ($file) {
            unlink($file);
        }
    }

    /**
     * Loads the compiled code of the template
     * @param string $code Compiled template code
     * @param string $runtimeId Id of the compiled template function
     * @return null
     */
    protected function loadCode($code, $runtimeId) {
        $file = tempnam(sys_get_temp_dir(), 'frame-' . $runtimeId . '-');

        file_put_contents($file, '<?php ' . $code);

        include($file);

        $this->files[count($this->files) - 1] = $file;
    }

}
