<?php

namespace huqis\tokenizer;

use huqis\tokenizer\symbol\NestedSymbol;
use huqis\tokenizer\symbol\SimpleSymbol;
use huqis\tokenizer\symbol\StringSymbol;
use huqis\tokenizer\symbol\String2Symbol;
use huqis\tokenizer\symbol\SyntaxSymbol;

/**
 * Tokenizer for values of the template syntax
 */
class ValueTokenizer extends Tokenizer {

    /**
     * Constructs a new value tokenizer
     */
    public function __construct() {
        $this->addSymbol(new StringSymbol(true));
        $this->addSymbol(new String2Symbol(true));
        $this->addSymbol(new SimpleSymbol(SyntaxSymbol::FILTER));
        $this->addSymbol(new NestedSymbol(SyntaxSymbol::NESTED_OPEN, SyntaxSymbol::NESTED_CLOSE, $this, true));

        parent::setWillTrimTokens(true);
    }

}
