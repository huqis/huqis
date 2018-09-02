<?php

namespace huqis\block;

use huqis\tokenizer\symbol\SyntaxSymbol;
use huqis\TemplateCompiler;

/**
 * Filter block to apply filters on a template block
 */
class FilterTemplateBlock implements TemplateBlock {

    /**
     * Constructs a new filter block
     * @return null
     */
    public function __construct() {
        $this->counter = 0;
    }

    /**
     * Gets whether this block has a signature
     * @return boolean
     */
    public function hasSignature() {
        return true;
    }

    /**
     * Gets whether this block needs to be closed
     * @return boolean
     */
    public function needsClose() {
        return true;
    }

    /**
     * Compiles this block into the output buffer of the compiler
     * @param \huqis\TemplateCompiler $compiler Instance of the compiler
     * @param string $signature Signature as provided in the template
     * @param string $body Contents of the block body
     * @return null
     */
    public function compile(TemplateCompiler $compiler, $signature, $body) {
        $buffer = $compiler->getOutputBuffer();
        $context = $compiler->getContext();

        $this->counter++;

        $context = $context->createChild();
        if (strpos($signature, SyntaxSymbol::FILTER . SyntaxSymbol::OUTPUT_RAW) !== false) {
            $context->setAutoEscape(false);
        }

        $compiler->setContext($context);

        // create a closure from the body block
        $buffer->appendCode('$filter' . $this->counter . ' = function(TemplateContext $context) {');
        $buffer->startBufferBlock();

        $compiler->subcompile($body);

        $buffer->endBufferBlock();
        $buffer->appendCode('};');

        $code = $compiler->compileFilters('$filter' . $this->counter . '($context)', $signature, true);

        // call the closure and assign the result to the variable
        $buffer->appendCode('echo ' . $code . ';');
        $buffer->appendCode('unset($filter' . $this->counter . ');');

        $compiler->setContext($context->getParent());
    }

}
