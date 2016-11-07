<?php

namespace huqis\operator\expression;

use huqis\TemplateCompiler;

/**
 * Interface for a expression operator like +, -, <, ==, ...
 */
interface ExpressionOperator {

    /**
     * Compiles this expression
     * @param \huqis\TemplateCompiler $compiler
     * @var string $left Expresison left of the operator
     * @var string $right Expression right of the operator
     * @return string PHP operator
     */
    public function compile(TemplateCompiler $compiler, $left, $right);

}
