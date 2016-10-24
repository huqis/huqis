<?php

namespace frame\library\tokenizer;

use frame\library\tokenizer\symbol\SimpleSymbol;
use frame\library\tokenizer\symbol\StringSymbol;
use frame\library\tokenizer\symbol\SyntaxSymbol;

/**
 * Tokenizer for the modifier arguments
 */
class ModifierTokenizer extends Tokenizer {

    /**
     * Constructs a new modifier tokenizer
     * @return null
     */
    public function __construct() {
        $this->addSymbol(new StringSymbol());
        $this->addSymbol(new SimpleSymbol(SyntaxSymbol::MODIFIER_ARGUMENT));

        parent::setWillTrimTokens(false);
    }

}
