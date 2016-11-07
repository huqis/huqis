<?php

namespace huqis\tokenizer;

use huqis\tokenizer\symbol\NestedSymbol;
use huqis\tokenizer\symbol\SimpleSymbol;
use huqis\tokenizer\symbol\StringSymbol;
use huqis\tokenizer\symbol\SyntaxSymbol;

/**
 * Tokenizer for an expression of the template syntax
 */
class ExpressionTokenizer extends Tokenizer {

    /**
     * Constructs a new expression tokenizer
     * @return null
     */
    public function __construct() {
        $this->nestedTokenizer = new Tokenizer();
        $this->nestedTokenizer->addSymbol(new StringSymbol());
        $this->nestedTokenizer->addSymbol(new NestedSymbol(SyntaxSymbol::NESTED_OPEN, SyntaxSymbol::NESTED_CLOSE, $this, true));
        $this->nestedTokenizer->addSymbol(new NestedSymbol(SyntaxSymbol::ARRAY_OPEN, SyntaxSymbol::ARRAY_CLOSE, null, true));

        $this->tokenizers = [];
        $this->operators = array();

        parent::setWillTrimTokens(false);
    }

    /**
     * Sets an expression operator to this tokenizer
     * @param string $syntax Syntax of the expression operator
     * @return null
     */
    public function setOperator($syntax) {
        $this->operators[$syntax] = true;

        // keep one tokenizer with all symbols with the same length
        $syntaxLength = strlen($syntax);

        if (isset($this->tokenizers[$syntaxLength])) {
            $tokenizer = $this->tokenizers[$syntaxLength];
        } else {
            $tokenizer = new Tokenizer();
            $tokenizer->setWillTrimTokens(false);

            $this->tokenizers[$syntaxLength] = $tokenizer;
        }

        $tokenizer->setSymbol($syntax, new SimpleSymbol($syntax));
    }

    /**
     * Tokenizes the provided string
     * @param string $string String to tokenize
     * @return array Array with the tokens of this string as value
     */
    public function tokenize($string) {
        $result = [];

        // tokenize the nested tokenizer
        $result = $this->nestedTokenizer->tokenize($string);

        // tokenize on the operator tokenizers, biggest length first
        ksort($this->tokenizers);
        $tokenizers = array_reverse($this->tokenizers);

        foreach ($tokenizers as $tokenizer) {
            $tokens = [];

            foreach ($result as $token) {
                if (is_array($token) || isset($this->operators[$token])) {
                    $tokens[] = $token;

                    continue;
                }

                $tokenizerTokens = $tokenizer->tokenize($token);
                foreach ($tokenizerTokens as $token) {
                    $tokens[] = $token;
                }
            }

            $result = $tokens;
        }

        return $result;
    }

}
