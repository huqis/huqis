<?php

namespace frame\library\block;

use frame\library\TemplateCompiler;

/**
 * If block element
 * @see ElseIfTemplateBlock
 * @see ElseTemplateBlock
 */
class IfTemplateBlock implements TemplateBlock {

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

        $buffer->appendCode('if (' . $compiler->compileCondition($signature) . ') {');

        $context = $context->createChild();
        $context->setBlock('elseif', new ElseIfTemplateBlock(), true);
        $context->setBlock('else', new ElseTemplateBlock(), true);

        $compiler->setContext($context);
        $compiler->subcompile($body);
        $compiler->setContext($context->getParent());

        $buffer->appendCode('}');
    }

}
