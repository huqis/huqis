<?php

namespace frame\library\block;

use frame\library\exception\CompileTemplateException;
use frame\library\tokenizer\symbol\SyntaxSymbol;
use frame\library\tokenizer\ForeachTokenizer;
use frame\library\TemplateCompiler;

/**
 * Foreach block element
 * @see BreakBlockElement
 * @see ContinueBlockElement
 */
class ForeachTemplateBlock implements TemplateBlock {

    /**
     * Constructs the foreach block element
     * @return null
     */
    public function __construct() {
        $this->tokenizer = new ForeachTokenizer();
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
     * @param \frame\library\TemplateCompiler $compiler Instance of the compiler
     * @param string $signature Signature as provided in the template
     * @param string $body Contents of the block body
     * @return null
     */
    public function compile(TemplateCompiler $compiler, $signature, $body) {
        $buffer = $compiler->getOutputBuffer();
        $context = $compiler->getContext();

        // parse the signature to retrieve the key, value and or loop variable name
        $parts = [];
        $part = 'iterator';
        $expression = '';

        $tokens = $this->tokenizer->tokenize($signature);
        foreach ($tokens as $token) {
            if ($token === SyntaxSymbol::FOREACH_AS || $token === SyntaxSymbol::FOREACH_KEY || $token === SyntaxSymbol::FOREACH_LOOP) {
                $expression = trim($expression);
                if ($expression === '') {
                    throw new CompileTemplateException('Invalid foreach: variable expected');
                }

                if ($part === 'iterator') {
                    // first token, should be the array or iterator
                    $parts[$part] = $compiler->compileExpression($expression);
                } else {
                    // the as, key or loop variable name
                    $parts[$part] = $compiler->parseName($expression);
                }

                $part = $token;
                $expression = '';
            } else {
                $expression .= $token;
            }
        }

        // validate signature
        $expression = trim($expression);
        if ($expression === '') {
            throw new CompileTemplateException('Invalid foreach: variable expected');
        }

        $parts[$part] = $compiler->parseName($expression);

        if (!isset($parts[SyntaxSymbol::FOREACH_AS]) && !isset($parts[SyntaxSymbol::FOREACH_KEY]) && !isset($parts[SyntaxSymbol::FOREACH_LOOP])) {
            throw new CompileTemplateException('Could not compile foreach statement: use at least one of as, key or loop');
        }

        // compile foreach into the output buffer
        $this->counter++;
        $hasLoop = isset($parts[SyntaxSymbol::FOREACH_LOOP]);

        $buffer->appendCode('$foreach' . $this->counter . ' = ' . $parts['iterator'] . ';');
        $buffer->appendCode('if ($foreach' . $this->counter . ') { ');
        if ($hasLoop) {
            $buffer->appendCode('$foreach' . $this->counter . 'Index = 0;');
            $buffer->appendCode('$foreach' . $this->counter . 'Length = count($foreach' . $this->counter . ');');
        }

        $buffer->appendCode('foreach ($foreach' . $this->counter . ' as ' . (isset($parts[SyntaxSymbol::FOREACH_KEY]) ? '$foreach' . $this->counter . 'Key => ' : '') . '$foreach' . $this->counter . 'Value) {');

        if ($hasLoop) {
            $buffer->appendCode('$context->setVariable(\'' . $parts[SyntaxSymbol::FOREACH_LOOP] . '\', [');
            $buffer->appendCode('"index" => $foreach' . $this->counter . 'Index,');
            $buffer->appendCode('"revindex" => $foreach' . $this->counter . 'Length - $foreach' . $this->counter . 'Index,');
            $buffer->appendCode('"first" => $foreach' . $this->counter . 'Index === 0,');
            $buffer->appendCode('"last" => $foreach' . $this->counter . 'Index === $foreach' . $this->counter . 'Length - 1,');
            $buffer->appendCode('"length" => $foreach' . $this->counter . 'Length,');
            $buffer->appendCode(']);');
            $buffer->appendCode('$foreach' . $this->counter . 'Index++;');
        }

        if (isset($parts[SyntaxSymbol::FOREACH_AS])) {
            $buffer->appendCode('$context->setVariable(\'' . $parts[SyntaxSymbol::FOREACH_AS] . '\', $foreach' . $this->counter . 'Value);');
        }
        if (isset($parts[SyntaxSymbol::FOREACH_KEY])) {
            $buffer->appendCode('$context->setVariable(\'' . $parts[SyntaxSymbol::FOREACH_KEY] . '\', $foreach' . $this->counter . 'Key);');
        }

        $context = $context->createChild();
        $context->setBlock('break', new BreakTemplateBlock());
        $context->setBlock('continue', new ContinueTemplateBlock());
        $context->removeBlock('else');
        $context->removeBlock('elseif');

        $compiler->setContext($context);
        $compiler->subcompile($body);
        $compiler->setContext($context->getParent());


        $buffer->appendCode('}');

        if ($hasLoop) {
            $buffer->appendCode('$context->setVariable(\'' . $parts[SyntaxSymbol::FOREACH_LOOP] . '\', null);');
        }

        $buffer->appendCode('}');
        $buffer->appendCode('unset($foreach' . $this->counter . ');');
    }

}
