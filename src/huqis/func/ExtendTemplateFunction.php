<?php

namespace huqis\func;

use huqis\TemplateContext;

/**
 * Function to extend another template resource which needs to be rendered at
 * runtime. For dynamic extend blocks, used internally.
 *
 * Syntax: _extends(<resource>, <extends-template-code>)
 *
 * {_extends("my-template.tpl", "Display my {$variable}")}
 */
class ExtendTemplateFunction implements TemplateFunction {

    /**
     * Calls the function with the provided context and arguments
     * @param \huqis\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments) {
        $engine = $context->getEngine();

        $resource = array_shift($arguments);
        $extends = array_shift($arguments);
        $extends = str_replace('\\$', '$', $extends);

        if (!$resource) {
            throw new RuntimeTemplateException('Could not include template: no resource(s) provided');
        }

        return $engine->render($resource, [], $context, $extends);
    }

}
