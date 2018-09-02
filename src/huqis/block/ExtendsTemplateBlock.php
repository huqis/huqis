<?php

namespace huqis\block;

use huqis\exception\CompileTemplateException;
use huqis\helper\StringHelper;
use huqis\TemplateCompiler;

/**
 * Extends element block, to invalidate extends statements after the beginning
 * of the template. The actual compile happens inside the template compiler.
 */
class ExtendsTemplateBlock extends IncludeTemplateBlock {

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

        $resource = null;

        try {
            $resource = $compiler->compileScalarValue($signature);
            $isStaticTemplate = true;
        } catch (CompileTemplateException $exception) {
            $isStaticTemplate = false;
        }

        if ($isStaticTemplate) {
            $resource = mb_substr($resource, 1, -1);
            $code = $compiler->getContext()->getResourceHandler()->getResource($resource);
        } else {
            $body = str_replace('$', '\\$', $body);
            $body = StringHelper::escapeQuotes($body);

            $code = '{_extends(' . $signature . ', "' . $body . '")}';
        }

        try {
            $buffer->startExtends();

            $compiler->setContext($context->createChild());
            $compiler->subcompile($code);

            if ($isStaticTemplate) {
                $compiler->compileExtends($body);
            }
        } catch (CompileTemplateException $exception) {
            $e = new CompileTemplateException('Could not compile ' . $resource . ': syntax error on line ' . $exception->getLineNumber(), 0, $exception);
            $e->setResource($resource);
            $e->setLineNumber($exception->getLineNumber());

            throw $e;
        }
    }

}
