<?php

namespace frame\library\tokenizer;

use frame\library\tokenizer\symbol\NestedSymbol;
use frame\library\tokenizer\symbol\SimpleSymbol;
use frame\library\tokenizer\symbol\StringSymbol;
use frame\library\tokenizer\symbol\SyntaxSymbol;

/**
 * Tokenizer for a variable or a variable assignment
 */
class VariableTokenizer extends Tokenizer {

    /**
     * Constructs a new variable tokenizer
     * @return null
     */
    public function __construct() {
        $this->addSymbol(new SimpleSymbol(SyntaxSymbol::MODIFIER));
        $this->addSymbol(new SimpleSymbol(SyntaxSymbol::VARIABLE_SEPARATOR));
        $this->addSymbol(new NestedSymbol(SyntaxSymbol::NESTED_OPEN, SyntaxSymbol::NESTED_CLOSE, null, true));
        $this->addSymbol(new NestedSymbol(SyntaxSymbol::ARRAY_OPEN, SyntaxSymbol::ARRAY_CLOSE, null, true));
        $this->addSymbol(new StringSymbol());

        parent::setWillTrimTokens(false);
    }

}
