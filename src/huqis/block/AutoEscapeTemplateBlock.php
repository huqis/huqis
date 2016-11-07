<?php

namespace huqis\block;

use huqis\TemplateCompiler;

/**
 * Auto escape block element
 */
class AutoEscapeTemplateBlock implements TemplateBlock {

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

        if ($signature) {
            $autoEscape = $compiler->compileExpression($signature);
            if ($autoEscape === 'true') {
                $autoEscape = true;
            } elseif ($autoEscape === 'false') {
                $autoEscape = false;
            }
        } else {
            $autoEscape = true;
        }

        $context = $context->createChild();
        $context->setAutoEscape($autoEscape);

        $compiler->setContext($context);
        $compiler->subcompile($body);
        $compiler->setContext($context->getParent());
    }

}
