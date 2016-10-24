<?php

namespace frame\library\block;

use frame\library\exception\CompileTemplateException;
use frame\library\tokenizer\symbol\SyntaxSymbol;
use frame\library\tokenizer\FunctionTokenizer;
use frame\library\TemplateCompiler;

/**
 * Call block to pass the compiled body as argument
 * @see MacroBlockElement
 */
class CallTemplateBlock implements TemplateBlock {

    /**
     * Constructs a new macro template block
     * @return null
     */
    public function __construct() {
        $this->tokenizer = new FunctionTokenizer();
    }

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

        $tokens = $this->tokenizer->tokenize($signature);

        // validate the signature
        $numTokens = count($tokens);
        if ($numTokens < 3 || $numTokens > 4 || $tokens[1] !== SyntaxSymbol::NESTED_OPEN || $tokens[$numTokens - 1] !== SyntaxSymbol::NESTED_CLOSE) {
            throw new CompileTemplateException($signature . ' is an invalid call signature');
        }

        // parse the arguments from the signature
        $name = $compiler->parseName($tokens[0], false);
        $arguments = [];

        array_shift($tokens); // name
        array_shift($tokens); // (
        array_pop($tokens); // )

        if ($tokens) {
            $tokens = array_pop($tokens);

            $needsSeparator = false;
            foreach ($tokens as $argument) {
                if ($needsSeparator && $argument === SyntaxSymbol::FUNCTION_ARGUMENT) {
                    $needsSeparator = false;
                } elseif (!$needsSeparator && $argument !== SyntaxSymbol::FUNCTION_ARGUMENT) {
                    if ($argument === '$_call') {
                        // the $_call argument is the body of the block,
                        // rendered through a closure
                        $arguments[] = '$_call($context)';
                    } else {
                        // any other argument is considered an expression
                        $arguments[] = $compiler->compileExpression(trim($argument));
                    }

                    $needsSeparator = true;
                } else {
                    throw new CompileTemplateException($signature . ' is an invalid call signature');
                }
            }
        }

        // create a closure from the body block
        $buffer->appendCode('$_call = function(TemplateContext $context) { ');
        $buffer->startBufferBlock();

        $context = $context->createChild();

        $compiler->setContext($context);
        $compiler->subcompile($body);
        $compiler->setContext($context->getParent());

        $buffer->endBufferBlock();
        $buffer->appendCode(' };');

        // call the macro function with the compiled arguments
        if ($arguments) {
            $arguments = '[' . implode(', ', $arguments) . ']';
        } else {
            $arguments = '';
        }

        $buffer->appendCode('echo $context->call("' . $name . '", ' . $arguments . ');');
    }

}
