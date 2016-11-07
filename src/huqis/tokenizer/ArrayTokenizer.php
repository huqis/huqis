<?php

namespace huqis\tokenizer;

use huqis\tokenizer\symbol\NestedSymbol;
use huqis\tokenizer\symbol\SimpleSymbol;
use huqis\tokenizer\symbol\StringSymbol;
use huqis\tokenizer\symbol\SyntaxSymbol;

/**
 * Tokenizer for arrays of the template syntax
 */
class ArrayTokenizer extends Tokenizer {

    /**
     * Constructs a new array tokenizer
     * @return null
     */
    public function __construct() {
        $this->addSymbol(new StringSymbol());
        $this->addSymbol(new SimpleSymbol(SyntaxSymbol::ASSIGNMENT));
        $this->addSymbol(new SimpleSymbol(SyntaxSymbol::FUNCTION_ARGUMENT));
        $this->addSymbol(new NestedSymbol(SyntaxSymbol::NESTED_OPEN, SyntaxSymbol::NESTED_CLOSE, $this, true));

        parent::setWillTrimTokens(false);
    }

}
