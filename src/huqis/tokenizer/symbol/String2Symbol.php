<?php

namespace huqis\tokenizer\symbol;

/**
 * Nested symbol to match the strings by '
 */
class String2Symbol extends StringSymbol {

    /**
     * Symbol to open a nested token
     * @var string
     */
    const SYMBOL = "'";

}
