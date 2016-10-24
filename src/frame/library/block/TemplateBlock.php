<?php

namespace frame\library\block;

use frame\library\TemplateCompiler;

/**
 * Interface for a block element of the template syntax
 */
interface TemplateBlock {

    /**
     * Gets whether this block has a signature
     * @return boolean
     */
    public function hasSignature();

    /**
     * Gets whether this block needs to be closed
     * @return boolean
     */
    public function needsClose();

    /**
     * Compiles this block into the output buffer of the compiler
     * @param \frame\library\TemplateCompiler $compiler Instance of the compiler
     * @param string $signature Signature as provided in the template
     * @param string $body Contents of the block body
     * @return null
     */
    public function compile(TemplateCompiler $compiler, $signature, $body);

}
