<?php

namespace huqis\tokenizer;

use huqis\tokenizer\symbol\NestedSymbol;
use huqis\tokenizer\symbol\SimpleSymbol;
use huqis\tokenizer\symbol\StringSymbol;
use huqis\tokenizer\symbol\SyntaxSymbol;

/**
 * Tokenizer for values of the template syntax
 */
class ValueTokenizer extends Tokenizer {

    /**
     * Constructs a new value tokenizer
     * @return null
     */
    public function __construct() {
        $this->addSymbol(new StringSymbol());
        $this->addSymbol(new SimpleSymbol(SyntaxSymbol::MODIFIER));
        $this->addSymbol(new NestedSymbol(SyntaxSymbol::NESTED_OPEN, SyntaxSymbol::NESTED_CLOSE, $this, true));

        parent::setWillTrimTokens(true);
    }

}
