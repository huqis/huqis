<?php

namespace frame\library\block;

use frame\library\TemplateCompiler;

/**
 * Assign block to create or update a variable from a template block
 */
class AssignTemplateBlock implements TemplateBlock {

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

        // create a closure from the body block
        $buffer->appendCode('$_assign = function(TemplateContext $context) { ');
        $buffer->startBufferBlock();

        $context = $context->createChild();

        $compiler->setContext($context);
        $compiler->subcompile($body);
        $compiler->setContext($context->getParent());

        $buffer->endBufferBlock();
        $buffer->appendCode(' };');

        // call the closure and assign the result to the variable
        $buffer->appendCode('$context->setVariable("' . $name . '", $_assign($context));');
        $buffer->appendCode('unset($_assign);');
    }

}
