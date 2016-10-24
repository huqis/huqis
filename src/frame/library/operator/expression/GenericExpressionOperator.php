<?php

namespace frame\library\operator\expression;

use frame\library\TemplateCompiler;

/**
 * Generic operator for expressions like:
 * <left-expression> <operator> <right-expression>
 */
class GenericExpressionOperator implements ExpressionOperator {

    /**
     * Compiled operator
     * @var string
     */
    private $operator;

    /**
     * Constructs a new generic operator
     * @param string $operator Operator in the PHP language
     * @return null
     */
    public function __construct($operator) {
        $this->operator = $operator;
    }

    /**
     * Compiles this expression
     * @param \frame\library\TemplateCompiler $compiler
     * @var string $left Expresison left of the operator
     * @var string $right Expression right of the operator
     * @return string PHP operator
     * @see \frame\library\TemplateCompiler->compileExpression
     */
    public function compile(TemplateCompiler $compiler, $left, $right) {
        $context = $compiler->getContext();

        if (!is_array($left)) {
            return $this->compileSimple($compiler, $left, $right);
        }

        // multiple operators without nesting
        // eg $var1 + $var2 - $var3
        $leftIn = $left;
        $rightIn = $right;

        $operator = null;
        $left = '';
        $right = '';

        $isFirst = true;

        foreach ($leftIn as $leftData) {
            if ($isFirst) {
                $isFirst = false;

                $left = $leftData['left'];
                $operator = $context->getExpressionOperator($leftData['operator']);
            } else {
                $right .= $leftData['left'] . $leftData['operator'];
            }
        }

        $right .= $rightIn;

        return $operator->compile($compiler, $left, $right);
    }

    /**
     * Compiles this expression
     * @param \frame\library\TemplateCompiler $compiler
     * @var string $left Expresison left of the operator
     * @var string $right Expression right of the operator
     * @return string PHP operator
     */
    public function compileSimple(TemplateCompiler $compiler, $left, $right) {
        return $compiler->compileExpression($left) . ' ' . $this->operator . ' ' . $compiler->compileExpression($right);
    }

}
