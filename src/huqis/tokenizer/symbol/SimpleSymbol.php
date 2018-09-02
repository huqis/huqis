<?php

namespace huqis\tokenizer\symbol;

/**
 * Simple implementation of a tokenizer symbol
 */
class SimpleSymbol extends AbstractSymbol {

    /**
     * The symbol to tokenize on
     * @var string
     */
    protected $symbol;

    /**
     * Number of characters in the symbol
     * @var integer
     */
    protected $symbolLength;

    /**
     * Length of the symbol multiplied with -1
     * @var integer
     */
    protected $symbolOffset;

    /**
     * Constructs a new simple symbol
     * @param string $symbol The symbol to tokenize on
     * @param boolean $willIncludeSymbol Flag to set whether to include the
     * symbol in the tokenize result
     * @return null
     */
    public function __construct($symbol, $willIncludeSymbols = true) {
        $this->symbol = $symbol;
        $this->symbolLength = mb_strlen($symbol);
        $this->symbolOffset = $this->symbolLength * -1;
        $this->setWillIncludeSymbols($willIncludeSymbols);
    }

    /**
     * Checks for this symbol in the string which is being tokenized
     * @param string $process Current part of the string which is being
     * tokenized
     * @param string $toProcess Remaining part of the string which has not yet
     * been tokenized
     * @return null|array Null when the symbol was not found, an array with the
     * processed tokens if the symbol was found.
     */
    public function tokenize(&$process, $toProcess) {
        $processLength = mb_strlen($process);
        if ($processLength < $this->symbolLength || mb_substr($process, $this->symbolOffset) != $this->symbol) {
            return null;
        }

        $tokens = [];

        if ($processLength != $this->symbolLength) {
            $tokens[] = mb_substr($process, 0, $this->symbolOffset);
        }

        if ($this->willIncludeSymbols) {
            $tokens[] = $this->symbol;
        }

        return $tokens;
    }

}
