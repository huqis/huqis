<?php

namespace frame\library\block;

use frame\library\TemplateCompiler;

/**
 * Block element for an extendable block used when extending from another
 * template
 */
class BlockTemplateBlock implements TemplateBlock {

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
        $append = false;
        $prepend = false;

        if (substr($signature, -7) === ' append') {
            $name = substr($signature, 0, -7);
            $append = true;
        } elseif (substr($signature, -8) === ' prepend') {
            $name = substr($signature, 0, -8);
            $prepend = true;
        } else {
            $name = $signature;
        }

        $name = $compiler->compileScalarValue($name);

        if ($append) {
            $buffer->appendExtendableBlock($name);
        } elseif ($prepend) {
            $buffer->prependExtendableBlock($name);
        } else {
            $buffer->startExtendableBlock($name);
        }

        $compiler->subcompile($body);

        $buffer->endExtendableBlock($name);
    }

}
