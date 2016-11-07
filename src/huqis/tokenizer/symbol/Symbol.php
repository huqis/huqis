<?php

namespace huqis\tokenizer\symbol;

/**
 * A symbol used to tokenize a string
 */
interface Symbol {

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
    public function tokenize(&$inProcess, $toProcess);

    /**
     * Process the tokens after the tokenizer has done it's work
     * @param array $tokens Resulting tokens
     * @return array Processed tokens
     */
    public function postTokenize(array $tokens);

}
