<?php

namespace frame\library\tokenizer;

use frame\library\tokenizer\symbol\SimpleSymbol;
use frame\library\tokenizer\symbol\SyntaxSymbol;

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
