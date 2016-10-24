<?php

namespace frame\library\block;

use frame\library\tokenizer\symbol\SyntaxSymbol;
use frame\library\TemplateCompiler;

/**
 * Return block element, used in a macro block
 * @see MacroTemplateBlock
 */
class ReturnTemplateBlock implements TemplateBlock {

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
     * @param \frame\library\TemplateCompiler $compiler Instance of the compiler
     * @param string $signature Signature as provided in the template
     * @param string $body Contents of the block body
     * @return null
     */
    public function compile(TemplateCompiler $compiler, $signature, $body) {
        $buffer = $compiler->getOutputBuffer();
        $buffer->appendCode('return ');

        $compiler->subcompile(SyntaxSymbol::SYNTAX_OPEN . $signature . SyntaxSymbol::SYNTAX_CLOSE, true);

        $buffer->appendCode(';');
    }

}
