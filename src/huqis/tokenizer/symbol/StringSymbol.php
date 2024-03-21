<?php

namespace huqis\tokenizer\symbol;

/**
 * Nested symbol to match the strings by "
 */
class StringSymbol extends NestedSymbol {

    /**
     * Symbol to open a nested token
     * @var string
     */
    const SYMBOL = '"';

    /**
     * Constructs a new nested symbol
     */
    public function __construct($includeSymbols = false) {
        parent::__construct(static::SYMBOL, static::SYMBOL, null, true);

        $this->setEscapeSymbol('\\', false);
        $this->includeSymbols = $includeSymbols;
    }

    /**
     * Checks for this symbol in the string which is being tokenized.
     *
     * The argument inProcess is passed by reference. When processing more
     * then the inProcess argument, concat the processed substring from
     * the toProcess argument to the inProcess argument to make the
     * tokenizer skip the processed part.
     * @param string $inProcess Current part of the string which is being
     * tokenized.
     * @param string $toProcess Remaining part of the string which has not yet
     * been tokenized
     * @return null|array Null when the symbol was not found, an array with
     * the processed tokens if the symbol was found.
     */
    public function tokenize(&$inProcess, $toProcess) {
        $tokens = parent::tokenize($inProcess, $toProcess);
        if (!$tokens || !$this->includeSymbols) {
            return $tokens;
        }

        $newTokens = [];
        $inString = false;
        $string = '';

        foreach ($tokens as $token) {
            if ($inString) {
                if ($token === $inString) {
                    $newTokens[] = $string . $token;
                    $inString = false;
                    $string = '';
                } else {
                    $string .= $token;
                }
            } else {
                if ($token === StringSymbol::SYMBOL || $token === String2Symbol::SYMBOL) {
                    $inString = $token;
                    $string = $token;
                } else {
                    $newTokens[] = $token;
                }
            }
        }

        return $newTokens;
    }

}
