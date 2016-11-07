<?php

namespace huqis\block;

use huqis\exception\CompileTemplateException;
use huqis\tokenizer\symbol\SyntaxSymbol;
use huqis\tokenizer\FunctionTokenizer;
use huqis\TemplateCompiler;

/**
 * Function block element, used to define functions inline in the template
 */
class FunctionTemplateBlock implements TemplateBlock {

    /**
     * Constructs a new function template block
     * @return null
     */
    public function __construct() {
        $this->tokenizer = new FunctionTokenizer();
        $this->counter = 0;
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
     * @param \huqis\TemplateCompiler $compiler Instance of the compiler
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
            throw new CompileTemplateException($signature . ' is an invalid function signature');
        }

        // parse the arguments from the signature
        $name = $compiler->parseName($tokens[0], false);
        $arguments = [];
        $defaults = [];

        array_shift($tokens); // name
        array_shift($tokens); // (
        array_pop($tokens); // )

        if ($tokens) {
            $tokens = array_pop($tokens);

            $needsSeparator = false;
            $value = '';
            foreach ($tokens as $token) {
                if ($token === SyntaxSymbol::FUNCTION_ARGUMENT) {
                    if (!$needsSeparator) {
                        throw new CompileTemplateException($signature . ' is an invalid function signature');
                    }

                    $this->addArgument($compiler, $value, $arguments, $defaults);

                    $needsSeparator = false;
                    $value = '';
                } else {
                    $needsSeparator = true;
                    $value .= $token;
                }
            }

            if ($value) {
                $this->addArgument($compiler, $value, $arguments, $defaults);
            }
        }

        // compile the function to the output buffer
        if ($arguments) {
            $isFirst = true;

            $arguments = ', [\'' . implode('\', \'', $arguments) . '\']';
            $arguments .= ', [';
            foreach ($defaults as $index => $value) {
                $arguments .= (!$isFirst ? ', ' : '') . $index . ' => ' . $value;
                $isFirst = false;
            }
            $arguments .= ']';
        } else {
            $arguments = '';
        }

        $this->counter++;

        $buffer->appendCode('$function' . $this->counter . ' = function(TemplateContext $context) {');
        $buffer->setAllowOutput(true);

        $context = $context->createChild();
        $context->removeBlock('function');
        $context->setBlock('return', new ReturnTemplateBlock());

        $compiler->setContext($context);
        $compiler->subcompile($body);
        $compiler->setContext($context->getParent());

        $buffer->clearAllowOutput();
        $buffer->appendCode('};');
        $buffer->appendCode('$context->setFunction(\'' . $name . '\', new \huqis\func\FunctionTemplateFunction("' . $name . '", $function' . $this->counter . $arguments . '));');
    }

    /**
     * Compiles and add an argument to the provided arguments and defaults
     * arrays
     * @param \huqis\TemplateCompiler $compiler Instance of the compiler
     * @param string $value Argument value
     * @param array $arguments Parsed argument names
     * @param array $defaults Default values indexed on argument index in the
     * function signature
     * @return null
     */
    private function addArgument(TemplateCompiler $compiler, $value, array &$arguments, array &$defaults) {
        $default = null;

        $positionAssignment = strpos($value, SyntaxSymbol::ASSIGNMENT);
        if ($positionAssignment) {
            $default = substr($value, $positionAssignment + 1);
            $argument = substr($value, 0, $positionAssignment);
        } else {
            $argument = $value;
        }

        $arguments[] = $compiler->parseName(trim($argument));

        if ($default) {
            $defaults[count($arguments) - 1] = $compiler->compileScalarValue(trim($default));
        }
    }

}
