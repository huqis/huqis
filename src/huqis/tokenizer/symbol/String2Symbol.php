<?php

namespace huqis\tokenizer\symbol;

/**
 * Nested symbol to match the strings by '
 */
class String2Symbol extends NestedSymbol {

    /**
     * Symbol to open a nested token
     * @var string
     */
    const SYMBOL = "'";

    /**
     * Constructs a new nested symbol
     * @return null
     */
    public function __construct() {
        parent::__construct(self::SYMBOL, self::SYMBOL, null, true);

        $this->setEscapeSymbol('\\', false);
    }

}
