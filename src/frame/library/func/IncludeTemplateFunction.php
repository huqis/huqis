<?php

namespace frame\library\func;

use frame\library\TemplateContext;

/**
 * Function to include another template resource which needs to be rendered at
 * runtime. For dynamic include blocks, used internally.
 *
 * Syntax: include(<resource>, [<resource-2>, [<resource-3>, [...]]])
 *
 * {_include("my-template.tpl")}
 * {_include("my-template.tpl", "my-second-template.tpl")}
 */
class IncludeTemplateFunction implements TemplateFunction {

    /**
     * Calls the function with the provided context and arguments
     * @param \frame\library\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments) {
        if (!$arguments) {
            return;
        }

        $engine = $context->getEngine();
        $variables = [];

        $resource = array_shift($arguments);
        if (!trim($resource)) {
            throw new RuntimeTemplateException('Could not include template: empty resource name provided');
        } elseif ($arguments) {
            $variables = array_shift($arguments);
        }

        return $engine->render($resource, $variables, $context);
    }

}
