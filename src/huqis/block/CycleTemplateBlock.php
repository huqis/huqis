<?php

namespace huqis\block;

use huqis\helper\StringHelper;
use huqis\TemplateCompiler;

/**
 * Cycle element values in a loop or whenever this block is encountered
 * @see ForeachBlockElement
 */
class CycleTemplateBlock implements TemplateBlock {

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

        if (!trim($signature)) {
            throw new CompileTemplateException('Could not compile cycle block: no values provided');
        }

        $name = '_cycle' . md5($signature);
        $arguments = $compiler->compileExpression($signature);
        $arguments = '[$context->ensureArray(' . $arguments . ', "Could not cycle ' . StringHelper::escapeQuotes($signature) . ': values should be an array")]';

        $buffer->appendCode('if (!$context->hasFunction(\'' . $name . '\')) {');
        $buffer->appendCode('$' . $name . ' = new \huqis\func\CycleTemplateFunction();');
        $buffer->appendCode('$context->setFunction(\'' . $name . '\', $' . $name . ');');
        $buffer->appendCode('}');
        $buffer->appendCode('echo $context->call(\'' . $name . '\', ' . $arguments . ');');
    }

}
