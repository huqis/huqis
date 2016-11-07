<?php

namespace huqis\block;

use huqis\TemplateCompiler;

/**
 * Elseif block element
 * @see ElseTemplateBlock
 * @see IfTemplateBlock
 */
class ElseIfTemplateBlock implements TemplateBlock {

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
        return false;
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

        $buffer->appendCode('} elseif (' . $compiler->compileCondition($signature) . ') {');

        $context = $context->getParent()->createChild();
        $context->setBlock('elseif', new ElseIfTemplateBlock(), true);
        $context->setBlock('else', new ElseTemplateBlock(), true);

        $compiler->setContext($context);
    }

}
