<?php

namespace huqis\tokenizer;

use huqis\tokenizer\symbol\NestedSymbol;
use huqis\tokenizer\symbol\SimpleSymbol;
use huqis\tokenizer\symbol\StringSymbol;
use huqis\tokenizer\symbol\String2Symbol;
use huqis\tokenizer\symbol\SyntaxSymbol;

/**
 * Tokenizer for a variable or a variable assignment
 */
class VariableTokenizer extends Tokenizer {

    /**
     * Constructs a new variable tokenizer
     * @return null
     */
    public function __construct() {
        $this->addSymbol(new SimpleSymbol(SyntaxSymbol::FILTER));
        $this->addSymbol(new SimpleSymbol(SyntaxSymbol::VARIABLE_SEPARATOR));
        $this->addSymbol(new NestedSymbol(SyntaxSymbol::NESTED_OPEN, SyntaxSymbol::NESTED_CLOSE, null, true));
        $this->addSymbol(new NestedSymbol(SyntaxSymbol::ARRAY_OPEN, SyntaxSymbol::ARRAY_CLOSE, null, true));
        $this->addSymbol(new StringSymbol());
        $this->addSymbol(new String2Symbol());

        parent::setWillTrimTokens(false);
    }

}
