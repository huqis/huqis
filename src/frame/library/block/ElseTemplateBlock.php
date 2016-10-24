<?php

namespace frame\library\block;

use frame\library\TemplateCompiler;

/**
 * Else block element
 * @see ElseIfTemplateBlock
 * @see IfTemplateBlock
 */
class ElseTemplateBlock implements TemplateBlock {

    /**
     * Gets whether this block has a signature
     * @return boolean
     */
    public function hasSignature() {
        return false;
    }

    /**
     * Gets whether this block needs to be closed
     * @return boolean
     */
    public function needsClose() {
        return false;
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

        $buffer->endCodeBlock(true);
        $buffer->appendCode(' else ');
        $buffer->startCodeBlock();

        $compiler->setContext($context->getParent()->createChild());
    }

}
