<?php

namespace huqis\tokenizer\symbol;

use huqis\tokenizer\Tokenizer;

/**
 * Nested condition symbol for the tokenizer
 */
class ConditionSymbol extends NestedSymbol {

    /**
     * Constructs a new condition tokenizer
     * @return null
     */
    public function __construct(Tokenizer $tokenizer, $trimTokens = false) {
        parent::__construct(SyntaxSymbol::NESTED_OPEN, SyntaxSymbol::NESTED_CLOSE, $tokenizer, $trimTokens);
    }

    /**
     * Checks for this symbol in the string which is being tokenized.
     * @param string $inProcess Current part of the string which is being tokenized.
     * @param string $toProcess Remaining part of the string which has not yet been tokenized
     * @return null|array Null when the symbol was not found, an array with the processed tokens if the symbol was found.
     */
    public function tokenize(&$process, $toProcess) {
        $processLength = strlen($process);
        if ($processLength < $this->symbolOpenLength || substr($process, $this->symbolOpenOffset) != $this->symbolOpen) {
            return null;
        }

        $positionOpen = $processLength - $this->symbolOpenLength;
        $positionClose = $this->getClosePosition($toProcess, $positionOpen);
        $lengthProcess = strlen($process) + $positionOpen;

        $before = substr($process, 0, $positionOpen);
        if (trim($before) || !$this->isNestedCondition($toProcess, $positionClose)) {
            return null;
        }

        $between = substr($toProcess, $positionOpen + $this->symbolOpenLength, $positionOpen + $positionClose - $lengthProcess);
        if ($between === '') {
            return null;
        }

        $betweenTokens = $this->tokenizer->tokenize($between);

        $process .= $between . $this->symbolClose;

        return [$betweenTokens];
    }

    /**
     * Checks what comes after the close symbol. If it's empty or a condition operator, the process string will be seen as a condition
     * @param string $toProcess
     * @param integer $positionClose
     * @return boolean True if the process string is to be seen as a condition, false otherwise
     */
    private function isNestedCondition($toProcess, $positionClose) {
        $positionAfter = $positionClose + 1;

        $toProcess = trim(substr($toProcess, $positionAfter));

        if (!$toProcess) {
            return true;
        }

        $toProcessLength = strlen($toProcess);
        if ($toProcessLength > 2 && substr($toProcess, 0, 2) == trim(SyntaxSymbol::OPERATOR_OR)) {
            return true;
        }
        if ($toProcessLength > 3 && substr($toProcess, 0, 3) == trim(SyntaxSymbol::OPERATOR_AND)) {
            return true;
        }

        return false;
    }

}
