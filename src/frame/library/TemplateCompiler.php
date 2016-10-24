<?php

namespace frame\library;

use frame\library\exception\CompileTemplateException;
use frame\library\tokenizer\symbol\StringSymbol;
use frame\library\tokenizer\symbol\SyntaxSymbol;
use frame\library\tokenizer\ArrayTokenizer;
use frame\library\tokenizer\ComparisonTokenizer;
use frame\library\tokenizer\ConditionTokenizer;
use frame\library\tokenizer\ExpressionTokenizer;
use frame\library\tokenizer\FunctionTokenizer;
use frame\library\tokenizer\ModifierTokenizer;
use frame\library\tokenizer\SyntaxTokenizer;
use frame\library\tokenizer\ValueTokenizer;
use frame\library\tokenizer\VariableTokenizer;

use \Exception;

/**
 * Template compiler to compile template syntax into PHP code
 * @see TemplateEngine
 */
class TemplateCompiler {

    /**
     * Context to use
     * @var TemplateContext
     */
    private $context;

    /**
     * Flag to check if a compile function is running
     * @var boolean
     */
    private $isCompiling;

    /**
     * Output buffer of the running compile function
     * @var TemplateOutputBuffer
     */
    private $buffer;

    /**
     * Constructs a new template compiler
     * @param TemplateContext $context Initial context for a template
     * @return null
     */
    public function __construct(TemplateContext $context) {
        $this->syntaxTokenizer = new SyntaxTokenizer();
        $this->valueTokenizer = new ValueTokenizer();
        $this->variableTokenizer = new VariableTokenizer();
        $this->modifierTokenizer = new ModifierTokenizer();
        $this->functionTokenizer = new FunctionTokenizer();
        $this->conditionTokenizer = new ConditionTokenizer();
        $this->expressionTokenizer = new ExpressionTokenizer();
        $this->arrayTokenizer = new ArrayTokenizer();

        $logicalOperators = $context->getLogicalOperators();
        foreach ($logicalOperators as $syntax => $logicalOperator) {
            $this->conditionTokenizer->setOperator($syntax);
        }

        $expressionOperators = $context->getExpressionOperators();
        foreach ($expressionOperators as $syntax => $expressionOperator) {
            $this->expressionTokenizer->setOperator($syntax);
        }

        $this->context = $context;
        $this->buffer = null;
        $this->isCompiling = false;
    }

    /**
     * Sets the current template context
     * @param TemplateContext $context
     * @return null
     */
    public function setContext(TemplateContext $context) {
        $this->context = $context;
    }

    /**
     * Gets the current template context
     * @return TemplateContext Instance of the template context
     */
    public function getContext() {
        return $this->context;
    }

    /**
     * Gets the output buffer of the running compile function
     * @return TemplateOutputBuffer|null Instance of the output buffer when
     * compiling, null otherwise
     */
    public function getOutputBuffer() {
        return $this->buffer;
    }

    /**
     * Compiles the provided template
     * @param string $template Template code to compile
     * @return string PHP code of the compiled template
     * @throws \frame\library\exception\CompileTemplateException when this
     * method is called while already compiling or when the template syntax is
     * invalid
     */
    public function compile($template) {
        if ($this->isCompiling) {
            throw new CompileTemplateException('Could not compile the provided template: already compiling');
        }

        $this->isCompiling = true;
        $this->buffer = new TemplateOutputBuffer();

        $this->subcompile($template, false);

        $result = (string) $this->buffer;

        $this->buffer = null;
        $this->isCompiling = false;

        return $result;
    }

    /**
     * Compiles a part of a template and appends it to the compile buffer
     * @param string $template Template code to compile
     * @param boolean $strict Flag to see if the result is parsed for output.
     * Set to false to display the result, true is considered template logic.
     * @return null
     * @throws CompileTemplateException when this method is called outside of
     * a compile call or when the template syntax is invalid
     */
    public function subcompile($template, $strict = false) {
        if (!$this->isCompiling) {
            throw new CompileTemplateException('Could not subcompile the provided template: no main compile process running');
        }

        // tokenize on syntax tokens {}
        $tokens = $this->syntaxTokenizer->tokenize($template);

        $isSyntax = false;
        $isComment = false;
        $tokenIndex = 0;
        $numTokens = count($tokens);

        // process the tokens
        while ($tokenIndex < $numTokens) {
            $token = $tokens[$tokenIndex];

            if ($token === SyntaxSymbol::SYNTAX_OPEN) {
                if (!$isComment && isset($tokens[$tokenIndex + 1]) && substr($tokens[$tokenIndex + 1], 0, 1) === SyntaxSymbol::COMMENT) {
                    // open comment
                    $isComment = true;
                    $tokenIndex++;

                    continue;
                } elseif (!$isSyntax) {
                    // open the syntax, only when there is no space after the symbol
                    $nextTokenIndex = $tokenIndex + 1;
                    if ($nextTokenIndex != $numTokens) {
                        $firstChar = substr($tokens[$nextTokenIndex], 0, 1);
                        if ($firstChar !== ' ' && $firstChar !== "\n" && $firstChar !== SyntaxSymbol::SYNTAX_CLOSE) {
                            // echo 'open syntax' . "\n";
                            $isSyntax = true;
                            $tokenIndex++;

                            continue;
                        }
                    }
                }
            } elseif ($token === SyntaxSymbol::SYNTAX_CLOSE) {
                if ($isComment && isset($tokens[$tokenIndex - 1]) && substr($tokens[$tokenIndex - 1], -1) === SyntaxSymbol::COMMENT) {
                    // close comment
                    $isComment = false;
                    $tokenIndex++;

                    continue;
                } elseif ($isSyntax) {
                    // close the syntax
                    $isSyntax = false;
                    $tokenIndex++;

                    continue;
                }
            }

            if ($isComment) {
                // comments
                $tokenIndex++;
            } elseif (!$isSyntax) {
                // no syntax, add plain text
                $this->buffer->appendText($token);
                $tokenIndex++;
            } else {
                // syntax, compile
                try {
                    $code = $this->compileSyntax($token, $tokens, $tokenIndex, $strict);
                } catch (Exception $exception) {
                    $lineNumber = 1;

                    for ($i = 0; $i < $tokenIndex; $i++) {
                        $lineNumber += substr_count($tokens[$i], "\n");
                    }

                    if ($exception instanceof CompileTemplateException && !$exception->getResource()) {
                        $previous = $exception->getPrevious();

                        $lineNumber += $exception->getLineNumber();
                        if ($previous instanceof CompileTemplateException) {
                            $lineNumber--;
                        }
                    }

                    $exception = new CompileTemplateException('Could not compile template on line ' . $lineNumber, 0, $exception);
                    $exception->setLineNumber($lineNumber);

                    throw $exception;
                }

                if ($code) {
                    $this->buffer->appendCode($code);
                }
            }
        }
    }

    /**
     * Compiles a syntax token. This is everything between { and }
     * @param string $token Current token
     * @param array $tokens All tokens of the template
     * @param integer $tokenIndex Current token index
     * @param boolean $strict Flag to see if the result is parsed for output.
     * Set to false to display the result, true is considered template logic.
     * @return string Compiled value
     */
    private function compileSyntax($token, $tokens, &$tokenIndex, $strict = false) {
        $positionSpace = strpos($token, ' ');
        if ($positionSpace === false) {
            $firstToken = $token;
        } else {
            $firstToken = substr($token, 0, $positionSpace);
        }

        $firstChar = substr($token, 0, 1);
        if ($firstChar !== '$' && !is_numeric($firstChar)) {
            $block = $this->compileBlock($firstToken, ltrim(substr($token, strlen($firstToken))), $tokens, $tokenIndex);
            if ($block !== false) {
                return $block;
            }
        }

        $tokenIndex++;

        return $this->compileExpression($token, $strict);
    }

    /**
     * Compiles a template block
     * @param string $name Name of the template block
     * @param string $signature Signature of the call
     * @param array $tokens All tokens of the template
     * @param integer $tokenIndex Current token index
     * @return string|boolean Compiled block is a valid block name, false
     * otherwise
     * @see \frame\library\block\TemplateBlock
     */
    private function compileBlock($name, $signature, array $tokens, &$tokenIndex) {
        if (!$this->context->hasBlock($name)) {
            return false;
        }

        $block = $this->context->getBlock($name);

        // recreate body of the block
        $body = '';
        $numTokens = count($tokens);
        $endTokenIndex = null;

        if ($block->needsClose()) {
            $nameLength = strlen($name);
            $recursive = 0;

            for ($i = $tokenIndex + 2; $i < $numTokens; $i++) {
                $token = $tokens[$i];
                $tokenLength = strlen($token);

                if (substr($token, 0, $nameLength) === $name) {
                    $recursive++;
                } elseif ($tokenLength === $nameLength + 1 && $token === '/' . $name) {
                    if ($recursive) {
                        $recursive--;
                    } else {
                        $body = substr($body, 0, -1);
                        $endTokenIndex = $i + 1;

                        break;
                    }
                }

                $body .= $token;
            }

            if ($endTokenIndex === null) {
                throw new CompileTemplateException('Block ' . $name . ' opened but not closed');
            }
        } else {
            $endTokenIndex = $tokenIndex + 1;
        }

        $block = $block->compile($this, $signature, $body);

        $tokenIndex = $endTokenIndex;

        return $block;
    }

    /**
     * Compiles an expression. This is everything between { and }
     * @param string $token Current token
     * @param array $tokens All tokens of the template
     * @param integer $tokenIndex Current token index
     * @param boolean $strict Flag to see if the result is parsed for output.
     * Set to false to display the result, true is considered template logic.
     * @param boolean $allowModifiers
     * @return string Compiled value
     */
    public function compileExpression($expression, $strict = true, $allowModifiers = true) {
        $result = '';
        $value = '';
        $operator = null;

        // check for array value
        $firstChar = substr($expression, 0, 1);
        $lastChar = substr($expression, -1);
        if ($firstChar === SyntaxSymbol::ARRAY_OPEN && $lastChar === SyntaxSymbol::ARRAY_CLOSE) {
            $result = $this->compileArray(substr($expression, 1, -1), $strict);
            if (!$strict) {
                $result .= ';';
            }

            return $result;
        }

        // tokenize on operators
        $tokens = $this->expressionTokenizer->tokenize($expression);

        // only 1 token, handle as value
        if (count($tokens) === 1) {
            return $this->compileValue($expression, $strict, $allowModifiers);
        }

        // process tokens
        $inArray = false;
        $array = '';

        $inString = false;
        $string = '';

        $left = null;
        $right = null;

        foreach ($tokens as $tokenIndex => $token) {
            if ($token === SyntaxSymbol::ASSIGNMENT) {
                $strict = true;
                // return $this->compileVariable($expression, $strict, $allowModifiers);
            }

            if ($token === StringSymbol::SYMBOL) {
                // string symbol
                if ($inString) {
                    $inString = false;
                    $value .= StringSymbol::SYMBOL . $string . StringSymbol::SYMBOL;
                    $string = '';
                } else {
                    $inString = true;
                }
            } elseif ($inString) {
                // token inside a string
                $string .= $token;
            } elseif ($token === SyntaxSymbol::ARRAY_OPEN) {
                $inArray = true;
            } elseif ($token === SyntaxSymbol::ARRAY_CLOSE) {
                $inArray = false;
                $value .= SyntaxSymbol::ARRAY_OPEN . $array . SyntaxSymbol::ARRAY_CLOSE;
                $array = '';
            } elseif ($inArray) {
                $array .= $token;
            } elseif (is_array($token)) {
                // rebuild nested token and add to current value
                $value .= SyntaxSymbol::NESTED_OPEN . $this->parseNested($token) . SyntaxSymbol::NESTED_CLOSE;
            } elseif ($this->context->hasExpressionOperator($token)) {
                // encountered operator
                $expressionOperator = $this->context->getExpressionOperator($token);

                $value = trim($value);
                if ($value === '') {
                    // no left value for operator
                    $value .= $token;

                    continue;
                }

                if (!$left) {
                    // keep compiled left value
                    $left = $value;
                } elseif (is_array($left)) {
                    // already a nested left expression
                    $left[] = ['operator' => $token, 'left' => $value];
                } else {
                    // already a nested left expression, nest it
                    $left = [
                        ['operator' => $operator, 'left' => $left],
                        ['operator' => $token, 'left' => $value],
                    ];
                }

                $operator = $token;

                $value = '';
            } else {
                // add to current value
                $value .= $token;
            }
        }

        if (!$operator) {
            return $this->compileValue($expression, $strict, $allowModifiers);
        }

        $right = trim($value);
        if ($right === '') {
            // no right value for operator
            throw new CompileTemplateException($expression . ' could not be parsed: no value before ' . $token);
        }

        $result .= $this->context->getExpressionOperator($operator)->compile($this, $left, $right);

        return $this->compileOutput($result, $strict);
    }

    /**
     * Compiles an array value
     * - ["value"]
     * - [$variable, $variable2]
     * @param string $array Array expression
     * @return string Compiled value
     */
    private function compileArray($array) {
        $result = '';
        $key = '';
        $expression = '';

        $tokens = $this->arrayTokenizer->tokenize($array);
        foreach ($tokens as $tokenIndex => $token) {
            if ($token === SyntaxSymbol::ASSIGNMENT) {
                $expression = trim($expression);
                if ($expression === '') {
                    throw new CompileTemplateException($array . ' could not be parsed: invalid syntax');
                }

                $key = $expression;

                $expression = '';
            } elseif ($token === SyntaxSymbol::FUNCTION_ARGUMENT) {
                $expression = trim($expression);
                if ($expression === '') {
                    throw new CompileTemplateException($array . ' could not be parsed: invalid syntax');
                }

                if ($result !== '') {
                    $result .= ', ';
                }

                if ($key) {
                    $result .= $this->compileExpression($key) . ' => ';
                }
                $result .= $this->compileExpression($expression);

                $expression = '';
                $key = '';
            } elseif (is_array($token)) {
                $expression .= $this->parseNested($token);
            } else {
                $expression .= $token;
            }
        }

        $expression = trim($expression);
        if ($expression === '') {
            throw new CompileTemplateException($array . ' could not be parsed: invalid syntax');
        }

        if ($result !== '') {
            $result .= ', ';
        }

        if ($key) {
            $result .= $this->compileExpression($key) . ' => ';
        }
        $result .= $this->compileExpression($expression);

        return '[' . $result . ']';
    }

    /**
     * Compiles a single value
     * Single values are:
     * - "value"
     * - $variable
     * - $variable.property
     * - $variable|truncate
     * - (15 + 7)|round
     * @param string $value Value expression
     * @param boolean $strict Flag to see if this is parsed for output, set to
     * false to display the result
     * @param boolean $allowModifiers
     * @return string Compiled value
     */
    private function compileValue($value, $strict = true, $allowModifiers = true) {
        // validate input
        if ($value === '') {
            throw new CompileTemplateException('Value or variable expected');
        } elseif ($value === '0' || $value === 0) {
            return $value;
        }

        $firstChar = substr($value, 0, 1);

        // check for not (!) symbol
        $isNot = false;
        if ($firstChar === SyntaxSymbol::OPERATOR_NOT) {
            $isNot = true;
            $value = substr($value, 1);
            $firstChar = substr($value, 0, 1);
        }

        // check for variable ($) symbol
        if ($firstChar == '$') {
            return ($isNot ? '!' : '') . $this->compileVariable($value, $strict);
        }

        $lastChar = substr($value, -1);

        // check for nested value
        $isNested = false;
        if ($firstChar === SyntaxSymbol::NESTED_OPEN && $lastChar === SyntaxSymbol::NESTED_CLOSE) {
            $isNested = true;

            $value = substr($value, 1, -1);
        }

        $tokens = $this->valueTokenizer->tokenize($value);
        $numTokens = count($tokens);

        if ($numTokens >= 3 && $tokens[0] == StringSymbol::SYMBOL && $tokens[2] == StringSymbol::SYMBOL) {
            $var = '"' . $tokens[1] . '"';
            $tokenIndex = 3;
        } else {
            $var = $tokens[0];
            $tokenIndex = 1;
        }

        $result = null;
        $expectsFunction = false;
        $issetToken = isset($tokens[$tokenIndex]);
        if (!$issetToken) {
            if ($isNested) {
                $result = $this->compileExpression($var);
            } else {
                try {
                    $result = $this->compileScalarValue($var);
                } catch (Exception $exception) {
                    if (strpos($value, SyntaxSymbol::NESTED_OPEN) !== strlen($var)) {
                        // not a function
                        throw $exception;
                    }
                }
            }
        }

        if ($result === null) {
            $modifiers = [];
            $hasHandledFunction = false;

            do {
                if (!$expectsFunction && $tokens[$tokenIndex] === SyntaxSymbol::NESTED_OPEN) {
                    $hasHandledFunction = false;
                    $expectsFunction = true;
                    $tokenIndex++;

                    continue;
                } elseif ($expectsFunction && $tokens[$tokenIndex] === SyntaxSymbol::NESTED_CLOSE) {
                    $expectsFunction = false;
                    $tokenIndex++;

                    if (!$hasHandledFunction) {
                        // no arguments
                        $functionName = $this->parseName($var, false);
                        $result = '$context->call(\'' . $functionName . '\')';
                    }

                    continue;
                } elseif ($expectsFunction || is_array($tokens[$tokenIndex])) {
                    // function
                    $functionName = $this->parseName($var, false);
                    if (!$issetToken) {
                        $result = '$context->call(\'' . $functionName . '\')';
                    } elseif ($functionName === 'isset') {
                        $result = 'isset(' . $this->compileFunction($tokens[$tokenIndex]) . ')';
                    } else {
                        $functionSignature = substr($value, strlen($functionName) + 1, -1);

                        $result = '$context->call(\'' . $functionName . '\', [' . $this->compileFunction($tokens[$tokenIndex]) . '])';
                    }

                    $tokenIndex++;
                    $hasHandledFunction = true;

                    continue;
                } elseif ($expectsFunction) {
                    throw new CompileTemplateException('Function signature expected');
                } elseif ($tokens[$tokenIndex] !== SyntaxSymbol::MODIFIER) {
                    throw new CompileTemplateException('Modifier expected');
                } elseif (!$allowModifiers) {
                    throw new CompileTemplateException('No modifiers allowed');
                }

                if ($result === null) {
                    $result = $this->compileScalarValue($var);
                }

                $modifiers[] = $this->compileModifiers($tokens, $tokenIndex);
            } while (isset($tokens[$tokenIndex]));

            // apply modifiers
            if ($modifiers) {
                $result = '$context->applyModifiers(' . $result . ', [' . implode(', ', $modifiers) . '])';
            }
        }

        if ($isNested) {
            $result = '(' . $result . ')';
        }
        if ($isNot) {
            $result = '!' . $result;
        }

        return $this->compileOutput($result, $strict);
    }

    private function compileVariable($variable, $strict = true) {
        try {
            $result = '$context->getVariable(\'' . $this->parseName($variable) . '\')';

            return $this->compileOutput($result, $strict);
        } catch (CompileTemplateException $exception) {
            // no simple variable, further code will compile
        }

        $tokens = $this->variableTokenizer->tokenize($variable);

        // parse modifiers and advanced assignments
        $result = null;
        $nested = '';
        $nestedLevel = 0;
        $array = '';
        $arrayLevel = 0;
        $modifiers = [];
        $tokenIndex = 1;
        $value = $tokens[0];

        while (isset($tokens[$tokenIndex])) {
            $token = $tokens[$tokenIndex];

            if ($token === SyntaxSymbol::ARRAY_OPEN) {
                if ($arrayLevel !== 0) {
                    $array .= $token;
                }

                $arrayLevel++;
                $tokenIndex++;

                continue;
            } elseif ($token === SyntaxSymbol::ARRAY_CLOSE) {
                $arrayLevel--;
                if ($arrayLevel !== 0) {
                    $array .= $token;
                } else {
                    if ($result) {
                        $result .= '[' . $this->compileExpression($array) . ']';
                    } else {
                        $name = $this->parseName($value);

                        $result = '$context->getVariable(\'' . $name . '\')[' . $this->compileExpression($array) . ']';
                    }

                    $array = '';
                    $value = '';
                }

                $tokenIndex++;

                continue;
            } elseif ($arrayLevel) {
                $array .= $token;
                $tokenIndex++;

                continue;
            } elseif ($token === SyntaxSymbol::NESTED_OPEN) {
                if ($nestedLevel !== 0) {
                    $nested .= $token;
                }

                $nestedLevel++;
                $tokenIndex++;

                continue;
            } elseif ($token === SyntaxSymbol::NESTED_CLOSE) {
                $nestedLevel--;
                if ($nestedLevel !== 0) {
                    $nested .= $token;
                } else {
                    if ($result) {
                        if (substr($value, 0, 1) !== SyntaxSymbol::VARIABLE_SEPARATOR) {
                            throw new CompileTemplateException('Cannot call a method here');
                        }

                        $result .= '->' . substr($value, 1) . '(' . $this->compileFunction($nested) . ')';
                    } else {
                        $name = $this->parseName($value);
                        $nameTokens = explode(SyntaxSymbol::VARIABLE_SEPARATOR, $name);
                        if (count($nameTokens) === 1) {
                            // dynamic function call
                            $result = '$context->call($context->getVariable(\'' . $name . '\'), [' . $this->compileFunction($nested) . '])';
                        } else {
                            // straight function call
                            $method = array_pop($nameTokens);
                            $name = implode(SyntaxSymbol::VARIABLE_SEPARATOR, $nameTokens);

                            $result = '$context->getVariable(\'' . $name . '\')->' . $method . '(' . $this->compileFunction($nested) . ')';
                        }
                    }

                    $nested = '';
                    $value = '';
                }

                $tokenIndex++;

                continue;
            } elseif ($nestedLevel) {
                $nested .= $token;
                $tokenIndex++;

                continue;
            } elseif ($token === SyntaxSymbol::MODIFIER) {
                $modifiers[] = $this->compileModifiers($tokens, $tokenIndex);

                continue;
            }

            $value .= $token;

            $tokenIndex++;
        }

        // apply modifiers and output
        if ($result === null) {
            $result = '$context->getVariable(\'' . $this->parseName($value) . '\')';
        }
        if ($modifiers) {
            $result = '$context->applyModifiers(' . $result . ', [' . implode(', ', $modifiers) . '])';
        }

        return $this->compileOutput($result, $strict);
    }

    /**
     * Compiles the modifier tokens
     * @param array $tokens Tokens of the variable or value tokenizer
     * @param integer $tokenIndex Current token index
     * @return string Compiled arguments for the applyModifiers context function
     * @see TemplateContext
     */
    private function compileModifiers(array $tokens, &$tokenIndex) {
        $tokenIndex++;
        if ($tokenIndex == count($tokens)) {
            throw new CompileTemplateException(implode('', $tokens) . ' could not be parsed: name of a modifier expected after ' . SyntaxSymbol::MODIFIER);
        }

        $modifier = '';
        do {
            if (is_array($tokens[$tokenIndex])) {
                $modifier .= SyntaxSymbol::NESTED_OPEN . $this->parseNested($tokens[$tokenIndex]) . SyntaxSymbol::NESTED_CLOSE;
            } else {
                $modifier .= $tokens[$tokenIndex];
            }

            $tokenIndex++;
        } while (isset($tokens[$tokenIndex]) && ($tokens[$tokenIndex] !== SyntaxSymbol::MODIFIER && $tokens[$tokenIndex] !== ' '));

        $arguments = [];
        $argument = '';

        $tokens = $this->modifierTokenizer->tokenize($modifier);
        foreach ($tokens as $index => $token) {
            if ($index === 0) {
                continue;
            }

            if ($token == SyntaxSymbol::MODIFIER_ARGUMENT) {
                if ($argument !== '') {
                    $arguments[] = $this->compileExpression($argument, true, false);
                    $argument = '';
                }

                continue;
            }

            $argument .= $token;
        }

        if ($argument !== '') {
            $arguments[] = $this->compileExpression($argument, true, false);
        }

        array_unshift($arguments, var_export($tokens[0], true));

        return '[' . implode(', ', $arguments) . ']';
    }

    /**
     * Compiles function call arguments
     * @param string $signature Template function call arguments
     * @return string Compiled condition
     */
    private function compileFunction($signature) {
        $result = '';
        $expression = '';

        if (is_array($signature)) {
            $signature = $this->parseNested($signature);
        }

        $tokens = $this->functionTokenizer->tokenize($signature);
        foreach ($tokens as $tokenIndex => $token) {
            if ($token === SyntaxSymbol::FUNCTION_ARGUMENT) {
                if ($expression === '') {
                    throw new CompileTemplateException($signature . ' could not be parsed: invalid syntax');
                }

                if ($result !== '') {
                    $result .= ', ';
                }

                $result .= $this->compileExpression(trim($expression));

                $expression = '';
            } elseif (is_array($token)) {
                $expression .= $this->parseNested($token);
            } else {
                $expression .= $token;
            }
        }

        if ($expression) {
            if ($result !== '') {
                $result .= ', ';
            }
            $result .= $this->compileExpression(trim($expression));
        }

        return $result;
    }

    /**
     * Compiles a condition
     * @param string $condition Template syntax of the condition
     * @return string Compiled condition
     */
    public function compileCondition($condition) {
        $result = '';

        $tokens = $this->conditionTokenizer->tokenize($condition);
        $result .= $this->compileConditionTokens($tokens);

        return $result;
    }

    /**
     * Compiles the nested and logical operators of a condition
     * @param array $tokens Tokens from the condition tokenizer
     * @return string Compiled condition
     */
    private function compileConditionTokens(array $tokens) {
        $result = '';
        foreach ($tokens as $token) {
            if (is_array($token)) {
                $result .= SyntaxSymbol::NESTED_OPEN . $this->compileConditionTokens($token) . SyntaxSymbol::NESTED_CLOSE;

                continue;
            }

            $operator = $this->context->getLogicalOperator($token);
            if ($operator) {
                $result .= ' ' . $operator->getOperator() . ' ';
            } else {
                $result .= $this->compileExpression($token);
            }
        }

        return $result;
    }

    /**
     * Parses and validates a scalar value being a number, boolean or string
     * @param string $value Value to parse
     * @return mixed Parsed value
     * @throws \frame\library\exception\CompileTemplateException when the
     * provided value is not considered a scalar value
     */
    public function compileScalarValue($value) {
        if (is_numeric($value)) {
            return (double) $value;
        }

        if ($value === 'null' || $value === 'true' || $value === 'false') {
            return $value;
        }

        if (substr($value, 0, 1) === StringSymbol::SYMBOL && substr($value, -1) === StringSymbol::SYMBOL) {
            return $value;
        }

        throw new CompileTemplateException('Could not compile scalar value: "' . $value . '" is not a valid scalar value syntax');
    }

    /**
     * Applies the strict
     * @param string $output Compiled output of an expression
     * @param boolean $strict
     * @return string
     */
    private function compileOutput($output, $strict) {
        if (!$strict) {
            $output = 'echo ' . $output . ';';
        }

        return $output;
    }

    /**
     * Parses and validated a variable or function name
     * @param string $name Name value to parse
     * @param boolean $isVariable Flag to see if a variable name is expected
     * making a $ as first character mandatory (true) or forbidden (false)
     * @return mixed Parsed name, without a $ when a variable is expected
     * @throws \frame\library\exception\CompileTemplateException when the
     * provided name is not a valid variable or function name
     */
    public function parseName($name, $isVariable = true) {
        $firstChar = substr($name, 0, 1);
        if ($isVariable && $firstChar !== '$') {
            throw new CompileTemplateException('Invalid variable ' . $name);
        } elseif (!$isVariable && $firstChar === '$') {
            throw new CompileTemplateException('Invalid name ' . $name);
        }

        if ($isVariable) {
            $name = substr($name, 1);
        }

        $nameLength = strlen($name);
        for ($i = 0; $i < $nameLength; $i++) {
            $char = ord(substr($name, $i, 1));
            if (($char >= 48 && $char <= 58) || ($char >= 65 && $char <= 91) || ($char >= 97 && $char <= 123) || $char == 95) { // 0-9 || A-Z || a-z || _
                continue;
            } elseif ($isVariable && $char == 46) { // .
                continue;
            }

            if ($isVariable) {
                throw new CompileTemplateException('Invalid variable $' . $name);
            } else {
                throw new CompileTemplateException('Invalid name ' . $name);
            }
        }

        return $name;
    }

    /**
     * Parses nested tokens back into a string
     * @param array $nested Result of a nested tokenizer
     * @return string
     */
    private function parseNested(array $nested) {
        $result = '';
        $isOpen = false;

        foreach ($nested as $token) {
            if (is_array($token)) {
                if ($isOpen) {
                    $result .= $this->parseNested($token);
                } else {
                    $result .= SyntaxSymbol::NESTED_OPEN . $this->parseNested($token) . SyntaxSymbol::NESTED_CLOSE;
                }

                continue;
            }

            if ($token == SyntaxSymbol::NESTED_OPEN) {
                $isOpen = true;
            } else {
                $isOpen = false;
            }

            $result .= $token;
        }

        return $result;
    }

}
