<?php

namespace frame\library\tokenizer\symbol;

/**
 * Nested symbol to match the template syntax
 */
class SyntaxSymbol extends NestedSymbol {

    /**
     * Symbol to open template syntax
     * @var string
     */
    const SYNTAX_OPEN = '{';

    /**
     * Symbol to close template syntax
     * @var string
     */
    const SYNTAX_CLOSE = '}';

    /**
     * Symbol to open functions or nested expressions
     * @var string
     */
    const NESTED_OPEN = '(';

    /**
     * Symbol to close functions or nested expressions
     * @var string
     */
    const NESTED_CLOSE = ')';

    /**
     * Symbol to open an array
     * @var string
     */
    const ARRAY_OPEN = '[';

    /**
     * Symbol to close functions
     * @var string
     */
    const ARRAY_CLOSE = ']';

    /**
     * Symbol to open a nested token
     * @var string
     */
    const COMMENT = '*';

    /**
     * Symbol for a variable assignment
     * @var string
     */
    const ASSIGNMENT = '=';

    /**
     * Symbol for a variable separator
     * @var string
     */
    const VARIABLE_SEPARATOR = '.';

    /**
     * Symbol of a variable modifier
     * @var string
     */
    const MODIFIER = '|';

    /**
     * Symbol to get the value of a foreach
     * @var string
     */
    const FOREACH_AS = ' as ';

    /**
     * Symbol to get the key of a foreach
     * @var string
     */
    const FOREACH_KEY = ' key ';

    /**
     * Symbol to get the loop of a foreach
     * @var string
     */
    const FOREACH_LOOP = ' loop ';

    /**
     * Symbol to add arguments to an include
     * @var string
     */
    const INCLUDE_WITH = ' with ';

    /**
     * Symbol to separate function arguments
     * @var string
     */
    const FUNCTION_ARGUMENT = ',';

    /**
     * Logical NOT operator for a comparisson
     * @var string
     */
    const OPERATOR_NOT = '!';

    /**
     * Constructs a new syntax symbol
     * @return null
     */
    public function __construct() {
        parent::__construct(self::SYNTAX_OPEN, self::SYNTAX_CLOSE, null, true, true, false);
    }

}
