<?php

namespace huqis\tokenizer;

use huqis\tokenizer\symbol\SimpleSymbol;
use huqis\tokenizer\symbol\SyntaxSymbol;

/**
 * Tokenizer for the syntax tags of the template
 */
class SyntaxTokenizer extends Tokenizer {

    /**
     * Constructs a new syntax tokenizer
     * @return null
     */
    public function __construct() {
        $this->addSymbol(new SyntaxSymbol());
        $this->addSymbol(new SimpleSymbol("\n"));

        parent::setWillTrimTokens(false);
    }

}
