<?php

namespace frame\library\operator\expression;

use frame\library\TemplateCompiler;

/**
 * Operator for a regular expressions
 */
class RegexExpressionOperator extends GenericExpressionOperator {

    /**
     * Constructs a new regex operator
     * @return null
     */
    public function __construct() {
        parent::__construct('dummy');
    }

    /**
     * Compiles this expression
     * @param \frame\library\TemplateCompiler $compiler
     * @var string $left Expresison left of the operator
     * @var string $right Expression right of the operator
     * @return string PHP operator
     */
    public function compileSimple(TemplateCompiler $compiler, $left, $right) {
        return 'preg_match(' . $compiler->compileExpression($right) . ', ' . $compiler->compileExpression($left) . ')';
    }

}
