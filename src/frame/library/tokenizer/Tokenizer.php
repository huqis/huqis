<?php

namespace frame\library\tokenizer;

use frame\library\tokenizer\symbol\Symbol;

/**
 * String tokenizer
 */
class Tokenizer {

    /**
     * Array with tokenize symbols
     * @var array
     */
    private $symbols = [];

    /**
     * Flag to set whether the tokens will be trimmed
     * @var boolean
     */
    private $willTrimTokens = false;

    /**
     * Adds a tokenize symbol to this tokenizer
     * @param \frame\library\tokenizer\symbol\Symbol $symbol
     * @return null
     */
    public function addSymbol(Symbol $symbol) {
        $this->symbols[] = $symbol;
    }

    /**
     * Sets a named tokenize symbol to this tokenizer
     * @param string $name Name of the token
     * @param \frame\library\tokenizer\symbol\Symbol $symbol
     * @return null
     */
    public function setSymbol($name, Symbol $symbol) {
        $this->symbols[$name] = $symbol;
    }

    /**
     * Gets the defined symbols
     * @return array Array with the name of the symbol as key and the instance
     * as value
     * @see \frame\library\tokenizer\symbol\Symbol
     */
    public function getSymbols() {
        return $this->symbols;
    }

    /**
     * Tokenizes the provided string
     * @param string $string String to tokenize
     * @return array Array with the tokens of this string as value
     */
    public function tokenize($string) {
        if ($string == '') {
            return [];
        }

        $stringLength = strlen($string);
        $tokens = [];

        $toProcess = $string;
        $countToProcess = count($toProcess);
        $process = '';

        while ($countToProcess != 0 && strlen($process) < $countToProcess) {
            $process .= $toProcess[strlen($process)];

            foreach ($this->symbols as $symbol) {
                $previousProcess = $process;

                $symbolTokens = $symbol->tokenize($process, $toProcess);
                if ($symbolTokens !== null) {
                    foreach ($symbolTokens as $symbolToken) {
                        $tokens[] = $symbolToken;
                    }

                    $toProcess = substr($toProcess, strlen($process));
                    $process = '';

                    break;
                } elseif ($process != $previousProcess) {
                    break;
                }
            }

            $countToProcess = strlen($toProcess);
        }

        if (!empty($toProcess)) {
            $tokens[] = $toProcess;
        }

        return $this->postTokenize($tokens);
    }

    /**
     *
     */
    protected function postTokenize(array $tokens) {
        foreach ($this->symbols as $symbol) {
            $tokens = $symbol->postTokenize($tokens);
        }

        if ($this->willTrimTokens) {
            $tokens = $this->trimTokens($tokens);
        }

        return $tokens;
    }

    /**
     * Sets whether this tokenizer will trim the resulting tokens. Tokens which
     * are empty after trimming will be removed. Nested tokens are untouched.
     * @param boolean $willTrimTokens True to trim the tokens, false otherwise
     * @return null
     */
    public function setWillTrimTokens($willTrimTokens) {
        $this->willTrimTokens = $willTrimTokens;
    }

    /**
     * Gets whether this tokenizer will trim tokens. Tokens which are empty
     * after trimming will be removed. Nested tokens are untouched.
     * @return boolean
     */
    public function willTrimTokens() {
        return $this->willTrimTokens;
    }

    /**
     * Trims the provided tokens. Tokens which are empty after trimming will be
     * removed. Nested tokens are untouched.
     * @param array $tokens
     * @return array
     */
    private function trimTokens(array $tokens) {
        $newTokens = [];

        foreach ($tokens as $key => $token) {
            if (is_array($token)) {
                $newTokens[] = $token;

                continue;
            }

            $token = trim($token);
            if (!empty($token)) {
                $newTokens[] = $token;
            }
        }

        return $newTokens;
    }

}
