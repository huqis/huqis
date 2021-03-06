<?php

namespace huqis\func;

use huqis\exception\RuntimeTemplateException;
use huqis\TemplateContext;

/**
 * Function to call defined function blocks which are compiled as a function in
 * the compiled template. Used internally to create dynamic function blocks.
 * @see \huqis\block\FunctionTemplateBlock
 */
class FunctionTemplateFunction implements TemplateFunction {

    /**
     * Name of the function
     * @var string
     */
    private $name;

    /**
     * Callback for the function
     * @var callable
     */
    private $callback;

    /**
     * Argument mapping for incoming arguments to the function child context
     * variables. An array with the index of the incoming argument as key and
     * the variable name as value
     * @var array
     */
    private $arguments;

    /**
     * Default value mapping for incoming arguments to the function child
     * context variables. An array with the index of the incoming argument as
     * key and the default as value
     * @var array
     */
    private $defaults;

    /**
     * Constructs a new function
     * @param string $name Name of the function
     * @param callable $callback Callback for the function block function
     * @param array $arguments Argument mapping for incoming arguments to the
     * function child context variables. An array with the index of the incoming
     * argument as key and the variable name as value
     * @param array $defaults Default value mapping for incoming arguments to
     * the function child context variables. An array with the index of the
     * incoming argument as key and the default as value
     * @return null
     */
    public function __construct($name, $callback, array $arguments = [], $defaults = []) {
        $this->name = $name;
        $this->callback = $callback;
        $this->arguments = $arguments;
        $this->defaults = $defaults;
    }

    /**
     * Calls the function with the provided context and arguments
     * @param \huqis\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments) {
        $context = $context->createChild();
        $context->resetVariables();

        foreach ($this->arguments as $index => $name) {
            if (array_key_exists($index, $arguments)) {
                $context->setVariable($name, $arguments[$index]);
            } elseif (array_key_exists($index, $this->defaults)) {
                $context->setVariable($name, $this->defaults[$index]);
            } else {
                throw new RuntimeTemplateException('Could not call function ' . $this->name . ': missing argument ' . ($index + 1) . ' $' . $name);
            }
        }

        return call_user_func($this->callback, $context);
    }

}
