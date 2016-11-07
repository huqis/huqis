<?php

namespace huqis\tokenizer;

use huqis\tokenizer\symbol\SimpleSymbol;
use huqis\tokenizer\symbol\SyntaxSymbol;

/**
 * Tokenizer for a foreach signature
 */
class ForeachTokenizer extends Tokenizer {

    /**
     * Constructs a new foreach tokenizer
     * @return null
     */
    public function __construct() {
        $this->addSymbol(new SimpleSymbol(SyntaxSymbol::FOREACH_AS));
        $this->addSymbol(new SimpleSymbol(SyntaxSymbol::FOREACH_KEY));
        $this->addSymbol(new SimpleSymbol(SyntaxSymbol::FOREACH_LOOP));

        parent::setWillTrimTokens(false);
    }

}
