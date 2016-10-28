<?php

namespace frame\library\block;

use frame\library\TemplateCompiler;

/**
 * Capture block to create or update a variable from a template block
 */
class CaptureTemplateBlock implements TemplateBlock {

    /**
     * Constructs a new assign block
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
     * @param \frame\library\TemplateCompiler $compiler Instance of the compiler
     * @param string $signature Signature as provided in the template
     * @param string $body Contents of the block body
     * @return null
     */
    public function compile(TemplateCompiler $compiler, $signature, $body) {
        $buffer = $compiler->getOutputBuffer();
        $context = $compiler->getContext();

        // validate the signature as a variable
        $name = $compiler->parseName($signature);

        $this->counter++;

        // create a closure from the body block
        $buffer->appendCode('$capture' . $this->counter . ' = function(TemplateContext $context) { ');
        $buffer->startBufferBlock();

        $context = $context->createChild();

        $compiler->setContext($context);
        $compiler->subcompile($body);
        $compiler->setContext($context->getParent());

        $buffer->endBufferBlock();
        $buffer->appendCode(' };');

        // call the closure and assign the result to the variable
        $buffer->appendCode('$context->setVariable("' . $name . '", $capture' . $this->counter . '($context));');
        $buffer->appendCode('unset($capture' . $this->counter . ');');
    }

}
