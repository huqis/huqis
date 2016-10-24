<?php

namespace frame\library\func;

use frame\library\TemplateContext;

/**
 * Function to cycle values
 * @see \frame\library\block\CycleTemplateBlock
 */
class CycleTemplateFunction implements TemplateFunction {

    /**
     * Index of the values to print
     * @var string
     */
    private $index = 0;

    /**
     * Calls the function with the provided context and arguments
     * @param \frame\library\TemplateContext $context Context for the function
     * @param array $arguments Arguments for the function
     * @return mixed Result of the function
     */
    public function call(TemplateContext $context, array $arguments) {
        $values = $arguments[0];
        if (!is_array($values)) {
            throw new RuntimeTemplateException('Could not cycle values: values is not an array');
        }

        $index = 0;
        $firstValue = null;

        foreach ($values as $value) {
            if ($index == $this->index) {
                $this->index++;

                return $value;
            } elseif ($firstValue === null) {
                $firstValue = $value;
            }

            $index++;
        }

        $this->index = 1;

        return $firstValue;
    }

}
