<?php

namespace huqis\tokenizer\symbol;

use huqis\exception\TokenizeTemplateException;
use huqis\tokenizer\Tokenizer;

/**
 * Nested symbol for the tokenizer
 */
class NestedSymbol extends AbstractSymbol {

    /**
     * Tokenizer to tokenize the value between the open and close symbol
     * @var \huqis\tokenizer\Tokenizer
     */
    protected $tokenizer;

    /**
     * Open symbol of the token
     * @var string
     */
    protected $symbolOpen;

    /**
     * Length of the open symbol
     * @var integer
     */
    protected $symbolOpenLength;

    /**
     * Length of the open symbol multiplied with -1
     * @var integer
     */
    protected $symbolOpenOffset;

    /**
     * Close symbol of the token
     * @var string
     */
    protected $symbolClose;

    /**
     * Length of the close symbol
     * @var integer
     */
    protected $symbolCloseLength;

    /**
     * Flag to set whether to allow symbols before the open symbol
     * @var boolean
     */
    protected $allowsSymbolsBeforeOpen;

    /**
     * Escape symbol
     * @var string
     */
    protected $symbolEscape = null;

    /**
     * Length of the escape symbol
     * @var integer
     */
    protected $symbolEscapeLength;

    /**
     * Flag to see if the escape symbol should be removed
     * @var boolean
     */
    protected $removeSymbolEscape;

    /**
     * Constructs a new nested tokenizer
     * @param string $symbolOpen Open symbol of the token
     * @param string $symbolClose Close symbol of the token
     * @param \huqis\tokenizer\Tokenizer $tokenizer When provided, the
     * value between the open and close symbol will be tokenized using this
     * tokenizer
     * @param boolean $willIncludeSymbols True to include the open and close
     * symbol in the tokenize result, false otherwise
     * @return null
     */
    public function __construct($symbolOpen, $symbolClose, Tokenizer $tokenizer = null, $willIncludeSymbols = false, $allowsSymbolsBeforeOpen = true, $isStrict = true) {
        $this->setOpenSymbol($symbolOpen);
        $this->setCloseSymbol($symbolClose);
        $this->setWillIncludeSymbols($willIncludeSymbols);
        $this->setAllowsSymbolsBeforeOpen($allowsSymbolsBeforeOpen);

        $this->isStrict = $isStrict;

        $this->tokenizer = $tokenizer;
    }

    /**
     * Sets the open symbol
     * @param string $symbol
     * @return null
     * @throws \ride\library\tokenizer\exception\TokenizerException when the provided symbol is empty or not a
     * string
     */
    private function setOpenSymbol($symbol) {
        if (!is_string($symbol) || $symbol == '') {
            throw new TokenizeTemplateException('Provided open symbol is empty or not a string');
        }

        $this->symbolOpen = $symbol;
        $this->symbolOpenLength = mb_strlen($symbol);
        $this->symbolOpenOffset = $this->symbolOpenLength * -1;
    }

    /**
     * Sets the close symbol
     * @param string $symbol
     * @return null
     * @throws \ride\library\tokenizer\exception\TokenizerException when the provided symbol is empty or not a
     * string
     */
    private function setCloseSymbol($symbol) {
        if (!is_string($symbol) || $symbol == '') {
            throw new TokenizeTemplateException('Provided close symbol is empty or not a string');
        }

        $this->symbolClose = $symbol;
        $this->symbolCloseLength = mb_strlen($symbol);
    }

    /**
     * Sets the escape symbol
     * @param string $escape
     * @return null
     */
    public function setEscapeSymbol($escape, $remove = true) {
        $this->symbolEscape = $escape;
        $this->symbolEscapeLength = mb_strlen($escape);
        $this->removeSymbolEscape = $remove;
    }

    /**
     * Sets whether to allow symbols before the open symbol
     * @param boolean $flag
     * @return null
     */
    public function setAllowsSymbolsBeforeOpen($flag) {
        $this->allowsSymbolsBeforeOpen = $flag;
    }

    /**
     * Gets whether to allow symbols before the open symbol
     * @return boolean
     */
    public function allowsSymbolsBeforeOpen() {
        return $this->allowsSymbolsBeforeOpen;
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
        if ($processLength < $this->symbolOpenLength || mb_substr($process, $this->symbolOpenOffset) != $this->symbolOpen) {
            return null;
        } elseif ($this->symbolEscape && mb_substr($process, $this->symbolOpenOffset - $this->symbolEscapeLength, $this->symbolEscapeLength) == $this->symbolEscape) {
            return null;
        }

        $positionOpen = $processLength - $this->symbolOpenLength;
        $positionClose = $this->getClosePosition($toProcess, $positionOpen);
        if ($positionClose === false) {
            return null;
        }

        $lengthProcess = mb_strlen($process) + $positionOpen;

        $before = mb_substr($process, 0, $positionOpen);
        if (!$this->allowsSymbolsBeforeOpen && trim($before)) {
            return null;
        }

        $between = mb_substr($toProcess, $positionOpen + $this->symbolOpenLength, $positionOpen + $positionClose - $lengthProcess);

        $process .= $between . $this->symbolClose;

        if ($between !== '' && $this->tokenizer !== null) {
            $between = $this->tokenizer->tokenize($between);
        }

        $result = [];

        if ($before !== '') {
            $result[] = $before;
        }

        if ($this->willIncludeSymbols) {
            $result[] = $this->symbolOpen;
            if ($between !== '') {
                $result[] = $between;
            }
            $result[] = $this->symbolClose;
        } elseif ($between !== '') {
            $result[] = $between;
        }

        return $result;
    }

    /**
     * Gets the position of the close symbol in a string
     * @param string $string String to look in
     * @param integer $initialOpenPosition The position of the open symbol for
     * which to find the close symbol
     * @return integer The position of the close symbol
     * @throws \ride\library\tokenizer\exception\TokenizeException when the symbol is opened but not closed
     */
    protected function getClosePosition($string, $initialOpenPosition) {
        $initialOpenPosition++;

        // look for first close
        $closePosition = strpos($string, $this->symbolClose, $initialOpenPosition);
        if ($closePosition === false) {
            if ($this->isStrict) {
                throw new TokenizeTemplateException($this->symbolOpen . ' opened (at ' . $initialOpenPosition . ') but not closed for ' . $string);
            } else {
                return false;
            }
        }

        // look if close symbol is escaped
        if ($this->symbolEscape && $closePosition > $this->symbolEscapeLength && mb_substr($string, $closePosition - $this->symbolEscapeLength, $this->symbolEscapeLength) == $this->symbolEscape) {
            // look is escape symbol is escaped
            $doubleSymbolEscapeLength = $this->symbolEscapeLength * 2;
            if (!($closePosition > $doubleSymbolEscapeLength && mb_substr($string, $closePosition - ($doubleSymbolEscapeLength), $doubleSymbolEscapeLength) == $this->symbolEscape . $this->symbolEscape)) {
                // close escaped, continue
                return $this->getClosePosition($string, $closePosition);
            }
        }

        // look for another open between initial open and close
        $openPosition = strpos($string, $this->symbolOpen, $initialOpenPosition);
        if ($openPosition === false || $openPosition > $closePosition || $this->symbolClose == $this->symbolOpen) {
            // no nested open
            return $closePosition;
        } elseif ($this->symbolEscape && mb_substr($string, $openPosition - $this->symbolEscapeLength, $this->symbolEscapeLength) == $this->symbolEscape) {
            // open is escaped
            return $closePosition;
        }

        $openClosePosition = $this->getClosePosition($string, $openPosition);

        return $this->getClosePosition($string, $openClosePosition);
    }

    /**
     * Process the tokens after the tokenizer has done it's work
     * @param array $tokens Resulting tokens
     * @return array Processed tokens
     */
    public function postTokenize(array $tokens) {
        if (!$this->symbolEscape || !$this->removeSymbolEscape) {
            return $tokens;
        }

        return $this->postProcessEscape($tokens);
    }

    private function postProcessEscape(array $tokens) {
        foreach ($tokens as $index => $token) {
            if (is_array($token)) {
                $tokens[$index] = $this->postProcessEscape($token);
            } else {
                $token = str_replace($this->symbolEscape . $this->symbolOpen, $this->symbolOpen, $token);
                $token = str_replace($this->symbolEscape . $this->symbolClose, $this->symbolClose, $token);

                $tokens[$index] = $token;
            }
        }

        return $tokens;
    }

}
