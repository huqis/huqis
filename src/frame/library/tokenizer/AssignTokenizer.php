<?php

namespace frame\library\tokenizer;

use frame\library\tokenizer\symbol\NestedSymbol;
use frame\library\tokenizer\symbol\StringSymbol;
use frame\library\tokenizer\symbol\SyntaxSymbol;

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
