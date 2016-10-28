<?php

namespace frame\library\operator\expression;

use frame\library\TemplateCompiler;

/**
 * Variable assignment operator
 * <left-expression> = <right-expression>
 */
class AssignExpressionOperator implements ExpressionOperator {

    /**
     * Compiles this expression
     * @param \frame\library\TemplateCompiler $compiler
     * @var string $left Expresison left of the operator
     * @var string $right Expression right of the operator
     * @return string PHP operator
     */
    public function compile(TemplateCompiler $compiler, $left, $right) {
        return '$context->setVariable("' . $compiler->parseName($left) . '", ' . $compiler->compileExpression($right) . ')';
    }

}
