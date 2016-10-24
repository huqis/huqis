<?php

namespace frame\library\func;

use frame\library\TemplateContext;

/**
 * Function to call defined macro blocks which are compiled as a function in the
 * compiled template. Used internally to create dynamic macro blocks.
 * @see \frame\library\block\MacroTemplateBlock
 */
class MacroTemplateFunction implements TemplateFunction {

    /**
     * Callback for the macro
     * @var callable
     */
    private $callback;

    /**
     * Argument mapping for incoming arguments to the macro child context
     * variables. An array with the index of the incoming argument as key and
     * the variable name as value
     * @var array
     */
    private $arguments;

    /**
     * Constructs a new macro function
     * @param callable $callback Callback for the macro block function
     * @param array $arguments Argument mapping for incoming arguments to the
     * macro child context variables. An array with the index of the incoming
     * argument as key and the variable name as value
     * @return null
     */
    public function __construct($callback, array $arguments = []) {
        $this->callback = $callback;
        $this->arguments = $arguments;
    }

    /**
     * Calls the function with the provided context and arguments
     * @param \frame\library\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments) {
        $contextArguments = [];

        foreach ($this->arguments as $index => $name) {
            if (isset($arguments[$index])) {
                $contextArguments[$name] = $arguments[$index];
            }
        }

        $context = $context->createChild();
        foreach ($contextArguments as $name => $value) {
            $context->setVariable($name, $value);
        }

        return call_user_func($this->callback, $context);
    }

}
