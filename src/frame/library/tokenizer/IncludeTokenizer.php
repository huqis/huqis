<?php

namespace frame\library\tokenizer;

use frame\library\tokenizer\symbol\SimpleSymbol;
use frame\library\tokenizer\symbol\SyntaxSymbol;

/**
 * Tokenizer for an include signature
 */
class IncludeTokenizer extends Tokenizer {

    /**
     * Constructs a new foreach tokenizer
     * @return null
     */
    public function __construct() {
        $this->addSymbol(new SimpleSymbol(SyntaxSymbol::INCLUDE_WITH));

        parent::setWillTrimTokens(false);
    }

}
