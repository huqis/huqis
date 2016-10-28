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
     * Flag to check if the compiler is initialized
     * @var boolean
     */
    private $isInitialized;

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
        $this->context = $context;
        $this->buffer = null;
        $this->isInitialized = false;
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
     * Initializes the compiler the first time it's used
     * @return null
     */
    private function initialize() {
        if ($this->isInitialized) {
            return;
        }

        $this->syntaxTokenizer = new SyntaxTokenizer();
        $this->valueTokenizer = new ValueTokenizer();
        $this->variableTokenizer = new VariableTokenizer();
        $this->arrayTokenizer = new ArrayTokenizer();
        $this->functionTokenizer = new FunctionTokenizer();
        $this->expressionTokenizer = new ExpressionTokenizer();
        $this->conditionTokenizer = new ConditionTokenizer();

        $expressionOperators = $this->context->getExpressionOperators();
        foreach ($expressionOperators as $syntax => $expressionOperator) {
            $this->expressionTokenizer->setOperator($syntax);
        }

        $logicalOperators = $this->context->getLogicalOperators();
        foreach ($logicalOperators as $syntax => $logicalOperator) {
            $this->conditionTokenizer->setOperator($syntax);
        }

        $this->isInitialized = true;
    }

    /**
     * Compiles the provided template
     * @param string $template Template code to compile
     * @param string $extends Template code from a dynamic extends block where
     * $template is the template code of the extended template
     * @return string PHP code of the compiled template
     * @throws \frame\library\exception\CompileTemplateException when this
     * method is called while already compiling or when the template syntax is
     * invalid
     */
    public function compile($template, $extends = null) {
        if ($this->isCompiling) {
            throw new CompileTemplateException('Could not compile the provided template: already compiling');
        }

        $this->initialize();

        $this->isCompiling = true;
        $this->buffer = new TemplateOutputBuffer();

        $this->subcompile($template, false);

        if ($extends) {
            $this->compileExtends($extends);
        }

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

        $expression = $this->compileExpression($token, $strict);

        return $this->compileOutput($expression, $strict);
    }

    /**
     * Compiles an extends block and ends the block
     * @param string $extends Template code from an extends block
     * @return null
     * @see \frame\library\block\ExtendsTemplateBlock
     * @see \frame\library\func\ExtendsTemplateFunction
     */
    public function compileExtends($extends) {
        $this->buffer->setAllowOutput(false);

        $this->subcompile($extends);

        $this->buffer->endExtends();
        $this->context = $this->context->getParent();
    }

    /**
     * Compiles a template block
     * @param string $name Name of the template block
     * @param string $signature Signature of the block
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

            $this->buffer->pushToBlockStack($name);
        } else {
            $endTokenIndex = $tokenIndex + 1;
        }

        $result = $block->compile($this, $signature, $body);

        if ($block->needsClose()) {
            $this->buffer->popFromBlockStack();
        }

        $tokenIndex = $endTokenIndex;

        return $result;
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
    public function compileExpression($expression, &$strict = true) {
        $result = '';
        $value = '';
        $operator = null;

        // check for array value
        $firstChar = substr($expression, 0, 1);
        $lastChar = substr($expression, -1);
        if ($firstChar === SyntaxSymbol::ARRAY_OPEN && $lastChar === SyntaxSymbol::ARRAY_CLOSE) {
            $strict = true;

            return $this->compileArray(substr($expression, 1, -1));
        }

        // tokenize on operators
        $tokens = $this->expressionTokenizer->tokenize($expression);

        // only 1 token, handle as value
        if (count($tokens) === 1) {
            return $this->compileValue($expression);
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
                // assignment
                $strict = true;
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
                // array open symbol
                $inArray = true;
            } elseif ($token === SyntaxSymbol::ARRAY_CLOSE) {
                // array close symbol
                $inArray = false;
                $value .= SyntaxSymbol::ARRAY_OPEN . $array . SyntaxSymbol::ARRAY_CLOSE;
                $array = '';
            } elseif ($inArray) {
                // token inside an array
                $array .= $token;
            } elseif (is_array($token)) {
                // rebuild nested token and add to current value
                $value .= $this->parseNested($token);
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
            return $this->compileValue($expression);
        }

        $right = trim($value);
        if ($right === '') {
            // no right value for operator
            throw new CompileTemplateException($expression . ' could not be parsed: no value before ' . $token);
        }

        $result .= $this->context->getExpressionOperator($operator)->compile($this, $left, $right);

        return $result;
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
     * @return string Compiled value
     */
    private function compileValue($value) {
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
            return ($isNot ? '!' : '') . $this->compileVariable($value);
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

        return $result;
    }

    private function compileVariable($variable) {
        try {
            $name = $this->parseName($variable);

            return $this->compileGetVariable($name);
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

                        $result = $this->compileGetVariable($name) . '[' . $this->compileExpression($array) . ']';
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
                        // double function call
                        // eg $object.method(...).method2(...)
                        if (substr($value, 0, 1) !== SyntaxSymbol::VARIABLE_SEPARATOR) {
                            throw new CompileTemplateException('Cannot call a method here');
                        }

                        $arguments = $this->compileFunction($nested);

                        $expression = '';
                        foreach ($tokens as $expressionTokenIndex => $token) {
                            $expression .= $token;

                            if ($expressionTokenIndex == $tokenIndex) {
                                break;
                            }
                        }

                        $expression = str_replace($value . '(' . $nested . ')', '', $expression);

                        $result = '$context->ensureObject(' . $result . ', \'Could not call ' . substr($variable, strlen($expression) + 1) . ': ' . $expression . ' is not an object\')';
                        $result .= '->' . substr($value, 1) . '(' . $arguments . ')';
                    } else {
                        $name = $this->parseName($value);
                        $nameTokens = explode(SyntaxSymbol::VARIABLE_SEPARATOR, $name);
                        if (count($nameTokens) === 1) {
                            // dynamic function call
                            // $function(...)
                            $result = '$context->call(' . $this->compileGetVariable($name) . ', [' . $this->compileFunction($nested) . '])';
                        } else {
                            // straight function call
                            // $object.method(...)
                            $method = array_pop($nameTokens);
                            $name = implode(SyntaxSymbol::VARIABLE_SEPARATOR, $nameTokens);

                            $result = '$context->ensureObject(' . $this->compileGetVariable($name) . ', \'Could not call ' . substr($variable, strlen($name) + 2) . ': $' . $name . ' is not an object\')';
                            $result .= '->' . $method . '(' . $this->compileFunction($nested) . ')';
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
            $result = $this->compileGetVariable($this->parseName($value));
        }
        if ($modifiers) {
            $result = '$context->applyModifiers(' . $result . ', [' . implode(', ', $modifiers) . '])';
        }

        return $result;
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
        $arguments = '';
        $value = '';
        $nested = 0;

        do {
            $token = $tokens[$tokenIndex];

            if (is_array($token)) {
                $value .= $this->parseNested($token);
            } elseif ($token == SyntaxSymbol::NESTED_OPEN && !$modifier) {
                if (!$value) {
                    throw new CompileTemplateException(implode('', $tokens) . ' could not be parsed: name of a modifier expexted after ' . SyntaxSymbol::MODIFIER);
                }

                $modifier = $value;
                $value = '';
            } elseif ($token == SyntaxSymbol::NESTED_OPEN) {
                $nested++;
            } elseif ($token == SyntaxSymbol::NESTED_CLOSE && $nested != 0) {
                $nested--;
            } elseif ($token == SyntaxSymbol::NESTED_CLOSE) {
                $arguments = $value;
                $value = '';
            } elseif ($arguments) {
                throw new CompileTemplateException(implode('', $tokens) . ' could not be parsed: ' . SyntaxSymbol::MODIFIER . ' expected');
            } else {
                $value .= $token;
            }

            $tokenIndex++;
        } while (isset($tokens[$tokenIndex]) && ($tokens[$tokenIndex] !== SyntaxSymbol::MODIFIER && $tokens[$tokenIndex] !== ' '));

        if ($value && !$modifier) {
            $modifier = $value;
        } elseif ($value) {
            throw new CompileTemplateException(implode('', $tokens) . ' could not be parsed: ' . SyntaxSymbol::NESTED_CLOSE . ' expected');
        }

        $result = '[';
        $result .= var_export($modifier, true);

        $arguments = $this->compileFunction($arguments);
        if ($arguments) {
            $result .= ', ' . $arguments;
        }

        $result .= ']';

        return $result;
    }

    /**
     * Compiles function call arguments
     * @param string $signature Template function call arguments
     * @return string Compiled function arguments
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
     * Compiles a get variable call
     * @param string $name Name of the variable
     * @return string
     */
    private function compileGetVariable($name) {
        $suffix = '';

        if (strpos($name, SyntaxSymbol::VARIABLE_SEPARATOR) === false) {
            $suffix = ', false';
        }

        return '$context->getVariable(\'' . $name . '\'' . $suffix . ')';
    }

    /**
     * Applies the strict
     * @param string $output Compiled output of an expression
     * @param boolean $strict
     * @return string
     */
    private function compileOutput($output, $strict) {
        if (!$strict) {
            $output = 'echo ' . $output;
        }

        return $output . ';';
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
