<?php

namespace huqis\block;

use huqis\TemplateCompiler;

/**
 * Parent block element, used to include the parent block
 * @see BlockTemplateBlock
 */
class ParentTemplateBlock implements TemplateBlock {

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
     * @param \huqis\TemplateCompiler $compiler Instance of the compiler
     * @param string $signature Signature as provided in the template
     * @param string $body Contents of the block body
     * @return null
     */
    public function compile(TemplateCompiler $compiler, $signature, $body) {
        $buffer = $compiler->getOutputBuffer();
        $buffer->appendParent();
    }

}
