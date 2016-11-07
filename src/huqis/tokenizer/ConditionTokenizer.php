<?php

namespace huqis\tokenizer;

use huqis\tokenizer\symbol\NestedSymbol;
use huqis\tokenizer\symbol\SimpleSymbol;
use huqis\tokenizer\symbol\SyntaxSymbol;

/**
 * Tokenizer for the conditions of the template syntax
 */
class ConditionTokenizer extends Tokenizer {

    /**
     * Constructs a new condition tokenizer
     * @return null
     */
    public function __construct() {
        $this->operatorTokenizer = new Tokenizer();

        $this->nestedTokenizer = new Tokenizer();
        $this->nestedTokenizer->addSymbol(new NestedSymbol(SyntaxSymbol::NESTED_OPEN, SyntaxSymbol::NESTED_CLOSE, $this, true));
    }

    /**
     * Adds a logical operator to this tokenizer
     * @param string $syntax Syntax of the logical operator
     * @return null
     */
    public function setOperator($syntax) {
        $this->operatorTokenizer->setSymbol($syntax, new SimpleSymbol($syntax));
    }

    /**
     * Tokenizes the provided string
     * @param string $string String to tokenize
     * @return array Array with the tokens of this string as value
     */
    public function tokenize($string) {
        $result = [];

        $tokens = $this->nestedTokenizer->tokenize($string);
        $this->addTokensToResult($result, $tokens);

        return $result;
    }

    /**
     * Processes the result of the nested tokenizer by concatting the function
     * calls and applying the operator tokenizer
     * @param array $result Result for this condition tokenizer
     * @param array $tokens Result of the nested tokenizer
     * @return null
     */
    private function addTokensToResult(array &$result, array $tokens) {
        $expression = '';

        $operators = $this->operatorTokenizer->getSymbols();

        foreach ($tokens as $tokenIndex => $token) {
            $lastResultToken = null;
            if ($result) {
                $lastResultToken = $result[count($result) - 1];
            }

            if ($token === SyntaxSymbol::NESTED_CLOSE && $result && is_array($lastResultToken)) {
                $expression = '';

                continue;
            }

            if (is_array($token)) {
                // nested syntax
                if ($expression === '') {
                    // no running expression and token already processed by the nested tokenizer
                    $result[] = $token;
                } elseif ($lastResultToken && !is_array($lastResultToken) && $this->endsWithOperator($lastResultToken, $operators)) {
                    // previous token ends with an operator
                    if ($expression != SyntaxSymbol::NESTED_OPEN) {
                        $this->addExpressionToResult($result, $expression);
                    }

                    $result[] = $token;
                    $expression = '';
                } elseif (substr($expression, -1) === SyntaxSymbol::NESTED_OPEN && $this->endsWithOperator($expression, $operators, -1)) {
                    // running expression ends with an open symbol prefixed with an operator
                    $this->addExpressionToResult($result, substr($expression, 0, -1));
                    $result[] = $token;
                    $expression = '';
                } elseif ($expression === SyntaxSymbol::NESTED_OPEN) {
                    // nested condition, already processed by the nested tokenizer
                    $result[] = $token;
                    $expression = '';
                } elseif ($this->endsWithOperator($expression, $operators)) {
                    // running expression ends with an operator
                    $this->addExpressionToResult($result, $expression);
                    $result[] = $token;
                    $expression = '';
                } else {
                    // function call
                    $expression .= implode('', $token);
                }
            } else {
                $expression .= $token;
            }
        }

        if ($expression !== '') {
            $this->addExpressionToResult($result, $expression);
        }

        $result = $this->filterWhiteSpaceTokens($result, $operators);
    }

    private function filterWhiteSpaceTokens(array $result, array $operators) {
        for ($index = 0, $count = count($result); $index < $count; $index++) {
            $token = $result[$index];
            if (is_array($token)) {
                continue;
            }

            if (trim($token) == '') {
                unset($result[$index]);

                continue;
            }

            $trimmedToken = trim($token);

            if (isset($operators[$trimmedToken])) {
                $result[$index] = $trimmedToken;
            }
        }

        return $result;
    }

    /**
     * Tokenizes an expression for operators and adds the result to the result
     * of this tokenizer
     * @param array $result Result for this condition tokenizer
     * @param string $expression Expression to add
     * @return null
     */
    private function addExpressionToResult(array &$result, $expression) {
        $expressionTokens = $this->operatorTokenizer->tokenize($expression);
        foreach ($expressionTokens as $expressionToken) {
            $result[] = $expressionToken;
        }
    }

    private function endsWithOperator($expression, array $operators, $offset = 0) {
        if ($offset) {
            $expression = substr($expression, 0, $offset);
        }

        $expression = trim($expression);

        foreach ($operators as $syntax => $operatorSymbol) {
            $syntax = trim($syntax);
            $syntaxLength = strlen($syntax);

            if (substr($expression, $syntaxLength * -1) == $syntax) {
                return true;
            }
        }

        return false;
    }

}
