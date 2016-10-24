<?php

namespace frame\library\block;

use frame\library\exception\CompileTemplateException;
use frame\library\TemplateCompiler;

/**
 * Include block element, used to include another template
 */
class IncludeTemplateBlock implements TemplateBlock {

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
        $resource = null;

        try {
            $resource = $compiler->compileScalarValue($signature);
            $isStaticTemplate = true;
        } catch (CompileTemplateException $exception) {
            $isStaticTemplate = false;
        }

        if ($isStaticTemplate) {
            $resource = substr($resource, 1, -1);
            $code = $compiler->getContext()->getResourceHandler()->getResource($resource);
        } else {
            $code = '{_include(' . $signature . ')}';
        }

        try {
            $compiler->subcompile($code);
        } catch (CompileTemplateException $exception) {
            $e = new CompileTemplateException('Could not compile ' . $resource . ': syntax error on line ' . $exception->getLineNumber(), 0, $exception->getPrevious());
            $e->setResource($resource);
            $e->setLineNumber($exception->getLineNumber());

            throw $e;
        }
    }

}
