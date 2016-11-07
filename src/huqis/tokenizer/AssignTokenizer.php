<?php

namespace huqis\tokenizer;

use huqis\tokenizer\symbol\NestedSymbol;
use huqis\tokenizer\symbol\StringSymbol;
use huqis\tokenizer\symbol\SyntaxSymbol;

/**
 * Tokenizer for the left part of an assignment
 */
class AssignTokenizer extends Tokenizer {

    /**
     * Constructs a new assign tokenizer
     * @return null
     */
    public function __construct() {
        $this->addSymbol(new StringSymbol());
        $this->addSymbol(new NestedSymbol(SyntaxSymbol::ARRAY_OPEN, SyntaxSymbol::ARRAY_CLOSE, null, true));

        parent::setWillTrimTokens(false);
    }

}
