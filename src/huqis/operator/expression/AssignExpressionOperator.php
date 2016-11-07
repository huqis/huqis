<?php

namespace huqis\operator\expression;

use huqis\exception\CompileTemplateException;
use huqis\tokenizer\symbol\SyntaxSymbol;
use huqis\tokenizer\AssignTokenizer;
use huqis\TemplateCompiler;

/**
 * Variable assignment operator
 * <left-expression> = <right-expression>
 */
class AssignExpressionOperator implements ExpressionOperator {

    /**
     * Constructs a new assignment operator
     * @return null
     */
    public function __construct() {
        $this->tokenizer = new AssignTokenizer();
    }

    /**
     * Compiles this expression
     * @param \huqis\TemplateCompiler $compiler
     * @var string $left Expresison left of the operator
     * @var string $right Expression right of the operator
     * @return string PHP operator
     */
    public function compile(TemplateCompiler $compiler, $left, $right) {
        $right = $compiler->compileExpression($right);

        if (!strpos($left, SyntaxSymbol::ARRAY_OPEN)) {
            // simple assignment without array syntax
            // eg $var = true
            return '$context->setVariable("' . $compiler->parseName($left) . '", ' . $right . (strpos($left, SyntaxSymbol::VARIABLE_SEPARATOR) ? '' : ', false') . ')';
        }

        // assignment with array syntax
        // eg $var[$key] = true

        $name = '';
        $arguments = '';
        $value = '';

        $tokens = $this->tokenizer->tokenize($left);
        foreach ($tokens as $token) {
            if ($token == SyntaxSymbol::ARRAY_OPEN) {
                if (!$name) {
                    $name = $compiler->parseName($value);
                } elseif ($value) {
                    throw new CompileTemplateException('Invalid syntax: no value allowed between ' . SyntaxSymbol::ARRAY_OPEN . ' and ' . SyntaxSymbol::ARRAY_OPEN);
                }

                $arguments .= '[';
                $value = '';
            } elseif ($token == SyntaxSymbol::ARRAY_CLOSE) {
                if ($value) {
                    $arguments .= $compiler->compileExpression($value);
                }

                $arguments .= ']';
                $value = '';
            } else {
                $value .= $token;
            }
        }

        if ($value) {
            throw new CompileTemplateException('Invalid syntax: expected ' . SyntaxSymbol::ARRAY_OPEN);
        }

        $result = '{ $assign = $context->getVariable(' . var_export($name, true) . '); ';
        $result .= '$assign' . $arguments . ' = ' . $right . '; ';
        $result .= '$context->setVariable(' . var_export($name, true) . ', $assign); ';
        $result .= 'unset($assign); }';

        return $result;
    }

}
