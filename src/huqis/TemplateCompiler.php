<?php

namespace huqis;

use huqis\exception\CompileTemplateException;
use huqis\exception\NotFoundTemplateException;
use huqis\tokenizer\symbol\StringSymbol;
use huqis\tokenizer\symbol\String2Symbol;
use huqis\tokenizer\symbol\SyntaxSymbol;
use huqis\tokenizer\ArrayTokenizer;
use huqis\tokenizer\ComparisonTokenizer;
use huqis\tokenizer\ConditionTokenizer;
use huqis\tokenizer\ExpressionTokenizer;
use huqis\tokenizer\FunctionTokenizer;
use huqis\tokenizer\SyntaxTokenizer;
use huqis\tokenizer\ValueTokenizer;
use huqis\tokenizer\VariableTokenizer;

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
     * Name of the current resource
     * @var string
     */
    private $resource;

    /**
     * Line number in the current resource
     * @var integer
     */
    private $lineNumber;

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
     * Gets the name in the template resource which is being compiled
     * @return string Name of the current template resource
     * @see getCompileLineNumber()
     */
    public function getCompileResource() {
        return $this->resource;
    }

    /**
     * Gets the location of the compiler in the current template
     * @return integer Line number in the current template resource
     * @see getCompileResource()
     */
    public function getCompileLineNumber() {
        return $this->lineNumber;
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
     * @param string $resource Name of the template resource for debugging
     * purposes
     * @param integer $indent Level of indentation
     * @param string $extends Template code from a dynamic extends block where
     * $template is the template code of the extended template
     * @return string PHP code of the compiled template
     * @throws \huqis\exception\CompileTemplateException when this
     * method is called while already compiling or when the template syntax is
     * invalid
     */
    public function compile($template, $resource = null, $indent = 0, $extends = null) {
        if ($this->isCompiling) {
            throw new CompileTemplateException('Could not compile ' . ($resource ? $resource : 'template') . ': already compiling, use subcompile() to compile code to the current buffer');
        }

        $this->initialize();

        $this->isCompiling = true;
        $this->lineNumber = 1;
        $this->buffer = new TemplateOutputBuffer();
        $this->buffer->setIndent($indent);

        $this->subcompile($template, $resource, $this->lineNumber);

        if ($extends) {
            $this->compileExtends($extends);
        }

        $result = (string) $this->buffer;

        $this->buffer = null;
        $this->isCompiling = false;
        $this->resource = null;
        $this->lineNumber = null;

        return $result;
    }

    /**
     * Compiles a part of a template and appends it to the compile buffer
     * @param string $template Template code to compile
     * @param string $resource Name of the current template resource for
     * debugging purposes
     * @param integer $lineNumber Line number in the current template resource
     * @param boolean $isLogic Flag to see if the result is parsed for output.
     * Set to false to display the result, true is considered template logic.
     * @throws CompileTemplateException when this method is called outside of
     * a compile call or when the template syntax is invalid
     */
    public function subcompile($template, $resource = null, $lineNumber = null, $isLogic = false) {
        if (!$this->isCompiling) {
            throw new CompileTemplateException('Could not subcompile ' . ($resource ? $resource : 'template') . ': not compiling at the moment, use compile() first');
        }

        // resolve resource and starting line number
        if (!$lineNumber) {
            $lineNumber = $this->lineNumber;

            if (!$resource && mb_strpos($template, "\n")) {
                $lineNumber++;
            }
        }

        if ($resource) {
            $this->resource = $resource;
        } else {
            $resource = $this->resource;
        }

        // tokenize on syntax tokens {}
        $template = str_replace("\r\n", "\n", $template);

        $tokens = $this->syntaxTokenizer->tokenize($template);

        $isComment = false;
        $isSyntax = false;
        $hasSyntax = false;

        $process = '';
        $line = '';
        $tokenIndex = 0;
        $numTokens = count($tokens);

        // process the tokens
        while ($tokenIndex < $numTokens) {
            $token = $tokens[$tokenIndex];

            if ($token === SyntaxSymbol::SYNTAX_OPEN) {
                $this->buffer->appendPosition($resource, $lineNumber);

                if (!$isComment && isset($tokens[$tokenIndex + 1]) && mb_substr($tokens[$tokenIndex + 1], 0, 1) === SyntaxSymbol::COMMENT) {
                    // open comment
                    $isComment = true;
                    $tokenIndex++;

                    continue;
                } elseif (!$isSyntax) {
                    // open the syntax, only when there is no space after the symbol
                    $nextTokenIndex = $tokenIndex + 1;
                    if ($nextTokenIndex != $numTokens) {
                        $firstChar = mb_substr($tokens[$nextTokenIndex], 0, 1);
                        if ($firstChar !== ' ' && $firstChar !== "\r" && $firstChar !== "\n" && $firstChar !== SyntaxSymbol::SYNTAX_CLOSE) {
                            // valid syntax opening
                            $isSyntax = true;
                            $tokenIndex++;

                            // some plain text in the process buffer
                            if ($process !== '') {
                                $this->buffer->appendText($process);
                                $process = '';
                            }

                            continue;
                        }
                    }
                }
            } elseif ($token === SyntaxSymbol::SYNTAX_CLOSE) {
                if ($isComment && isset($tokens[$tokenIndex - 1]) && mb_substr($tokens[$tokenIndex - 1], -1) === SyntaxSymbol::COMMENT) {
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
            } elseif ($token === "\n") {
                // new line
                if (!($hasSyntax && $line === '')) {
                    $process .= "\n";
                }

                $lineNumber++;
                $line = '';
                $hasSyntax = false;
                $tokenIndex++;

                continue;
            }

            if ($isComment) {
                // comments
                $tokenIndex++;
            } elseif (!$isSyntax) {
                // no syntax, add plain text
                $process .= $token;
                $line .= $token;
                $tokenIndex++;
            } else {
                // syntax, compile
                try {
                    // store current position in template
                    $previousTokenIndex = $tokenIndex;

                    $this->resource = $resource;
                    $this->lineNumber = $lineNumber;

                    // compile the syntax
                    $code = $this->compileSyntax($token, $tokens, $tokenIndex, $isLogic);

                    // add processed number of lines to current line number
                    for ($i = $previousTokenIndex; $i <= $tokenIndex; $i++) {
                        if ($tokens[$i] === "\n") {
                            $lineNumber++;
                        }
                    }

                    // store new current position
                    $this->resource = $resource;
                    $this->lineNumber = $lineNumber;
                } catch (Exception $exception) {
                    if ($exception instanceof NotFoundTemplateException) {
                        $exception = new CompileTemplateException('Template "' . $exception->getResource() . '" not found.', 0, $exception);
                    } else {
                        $exception = new CompileTemplateException('Could not compile "' . $resource . '" on line ' . $lineNumber, 0, $exception);
                    }
                    $exception->setResource($resource);
                    $exception->setLineNumber($lineNumber);

                    throw $exception;
                }

                $hasSyntax = true;

                if ($code !== '' && $code !== null) {
                    if (mb_substr($code, 0, 4) === 'echo') {
                        $line .= $code;
                    }

                    $this->buffer->appendCode($code);
                }
            }
        }

        if ($process !== '') {
            $this->buffer->appendText($process);
        }
    }

    /**
     * Compiles a syntax token. This is everything between { and }
     * @param string $token Current token
     * @param array $tokens All tokens of the template
     * @param integer $tokenIndex Current token index
     * @param boolean $isLogic Flag to see if the result is parsed for output.
     * Set to false to display the result, true is considered template logic.
     * @return string Compiled value
     */
    private function compileSyntax($token, $tokens, &$tokenIndex, $isLogic = false) {
        $positionSpace = strpos($token, ' ');
        if ($positionSpace === false) {
            $firstToken = $token;
        } else {
            $firstToken = mb_substr($token, 0, $positionSpace);
        }

        $firstChar = mb_substr($token, 0, 1);
        if ($firstChar !== '$' && !is_numeric($firstChar)) {
            $block = $this->compileBlock($firstToken, ltrim(mb_substr($token, mb_strlen($firstToken))), $tokens, $tokenIndex);
            if ($block !== false) {
                return $block;
            }
        }

        $tokenIndex++;

        $expression = $this->compileExpression($token, $isLogic);

        return $this->compileOutput($expression, $isLogic);
    }

    /**
     * Compiles an extends block and ends the block
     * @param string $extends Template code from an extends block
     * @param string $resource Name of the template resource for debugging
     * @param integer $lineNumber Line number in the template resource
     * @see \huqis\block\ExtendsTemplateBlock
     * @see \huqis\func\ExtendsTemplateFunction
     */
    public function compileExtends($extends, $resource = null, $lineNumber = null) {
        $this->buffer->setAllowOutput(false);

        $this->subcompile($extends, $resource, $lineNumber);

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
     * @see \huqis\block\TemplateBlock
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
            $nameLength = mb_strlen($name);
            $recursive = 0;

            for ($i = $tokenIndex + 2; $i < $numTokens; $i++) {
                $token = $tokens[$i];
                $tokenLength = mb_strlen($token);

                if (mb_substr($token, 0, $nameLength) === $name) {
                    // opening block
                    $recursive++;
                } elseif ($tokenLength === $nameLength + 1 && $token === '/' . $name) {
                    // closing block
                    if ($recursive) {
                        $recursive--;
                    } else {
                        $body = mb_substr($body, 0, -1);
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

        if (mb_substr($body, 0, 1) == "\n") {
            $body = mb_substr($body, 1);
        }

        $result = $block->compile($this, trim($signature), $body);

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
     * @param boolean $isLogic Flag to see if the result is parsed for output.
     * Set to false to display the result, true is considered template logic.
     * @param boolean $allowModifiers
     * @return string Compiled value
     */
    public function compileExpression($expression, &$isLogic = true) {
        $expression = trim($expression);

        $result = '';
        $value = '';
        $operator = null;
        $isNot = false;

        // check for array value
        $firstChar = mb_substr($expression, 0, 1);
        $lastChar = mb_substr($expression, -1);
        if ($firstChar === SyntaxSymbol::ARRAY_OPEN && $lastChar === SyntaxSymbol::ARRAY_CLOSE) {
            $isLogic = true;

            return $this->compileArray(mb_substr($expression, 1, -1));
        }

        // check for not (!) symbol
        if (mb_substr($expression, 0, 1) === SyntaxSymbol::OPERATOR_NOT) {
            $isNot = true;
            $expression = mb_substr($expression, 1);
        }

        // tokenize on operators
        $tokens = $this->expressionTokenizer->tokenize($expression);

        // only 1 token, handle as value
        if (count($tokens) === 1) {
            $result = $this->compileValue($expression, $isLogic);
            if ($isNot) {
                $result = '!' . $result;
            }

            return $result;
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
                $isLogic = true;
            }

            if ($inString && $token === $inString) {
                // close string symbol
                $value .= $inString . $string . $inString;
                $inString = false;
                $string = '';
            } elseif (!$inString && $token === StringSymbol::SYMBOL) {
                // string symbol "
                $inString = StringSymbol::SYMBOL;
            } elseif (!$inString && $token === String2Symbol::SYMBOL) {
                // string symbol '
                $inString = String2Symbol::SYMBOL;
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
                    // already a left expression, nest it
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
            $result = $this->compileValue($expression, $isLogic);
        } else {
            $right = trim($value);
            if ($right === '') {
                // no right value for operator
                throw new CompileTemplateException($expression . ' could not be parsed: no value before ' . $token);
            }

            $result .= $this->context->getExpressionOperator($operator)->compile($this, $left, $right);
        }

        if ($isNot) {
            $result = '!' . $result;
        }

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
        if (trim($array) == '') {
            return '[]';
        }

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
     * - true
     * - 15.6789
     * - $variable
     * - $variable.property
     * - $variable|truncate
     * - functionCall()
     * @param string $value Value expression
     * @param boolean $isLogic Flag to see if the result is parsed for output.
     * Set to false to display the result, true is considered template logic.
     * @return string Compiled value
     */
    private function compileValue($value, $isLogic) {
        // validate input
        if ($value === '') {
            throw new CompileTemplateException('Value or variable expected');
        } elseif ($value === '0' || $value === 0) {
            return $value;
        }

        $firstChar = mb_substr($value, 0, 1);

        // check for variable ($) symbol
        if ($firstChar == '$') {
            return $this->compileVariable($value, $isLogic);
        }

        $lastChar = mb_substr($value, -1);

        // check for nested value
        $isNested = false;
        if ($firstChar === SyntaxSymbol::NESTED_OPEN && $lastChar === SyntaxSymbol::NESTED_CLOSE) {
            $isNested = true;

            $value = mb_substr($value, 1, -1);
        }

        $tokens = $this->valueTokenizer->tokenize($value);
        $numTokens = count($tokens);

        if ($numTokens >= 2 && $tokens[0] == StringSymbol::SYMBOL && $tokens[1] == StringSymbol::SYMBOL) {
            // empty string token
            $var = '""';
            $tokenIndex = 2;
        } elseif ($numTokens >= 3 && $tokens[0] == StringSymbol::SYMBOL && $tokens[2] == StringSymbol::SYMBOL) {
            // string token
            $var = '"' . $tokens[1] . '"';
            $tokenIndex = 3;
        } elseif ($numTokens >= 2 && $tokens[0] == String2Symbol::SYMBOL && $tokens[1] == String2Symbol::SYMBOL) {
            // empty string token
            $var = '""';
            $tokenIndex = 2;
        } elseif ($numTokens >= 3 && $tokens[0] == String2Symbol::SYMBOL && $tokens[2] == String2Symbol::SYMBOL) {
            // string token
            $var = "'" . $tokens[1] . "'";
            $tokenIndex = 3;
        } else {
            $var = $tokens[0];
            $tokenIndex = 1;
        }

        $filters = [];
        $useOutputFilters = true;
        $result = null;
        $expectsFunction = false;

        $issetToken = isset($tokens[$tokenIndex]);
        if (!$issetToken) {
            if ($isNested) {
                $result = $this->compileExpression($var);
            } else {
                try {
                    $result = $this->compileScalarValue($var);
                    $useOutputFilters = false;
                } catch (Exception $exception) {
                    if (strpos($value, SyntaxSymbol::NESTED_OPEN) !== mb_strlen($var)) {
                        // not a function
                        throw $exception;
                    }
                }
            }
        }

        if ($result === null) {
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
                    if ($functionName === '_extends') {
                        $useOutputFilters = false;
                    }

                    if (!$issetToken) {
                        $result = '$context->call(\'' . $functionName . '\')';
                    } elseif ($functionName === 'isset') {
                        $result = 'isset(' . $this->compileFunction($tokens[$tokenIndex]) . ')';
                    } else {
                        $functionSignature = mb_substr($value, mb_strlen($functionName) + 1, -1);

                        $result = '$context->call(\'' . $functionName . '\', [' . $this->compileFunction($tokens[$tokenIndex]) . '])';
                    }

                    $tokenIndex++;
                    $hasHandledFunction = true;

                    continue;
                } elseif ($expectsFunction) {
                    throw new CompileTemplateException('Function signature expected');
                } elseif ($tokens[$tokenIndex] !== SyntaxSymbol::FILTER) {
                    throw new CompileTemplateException('Filter expected, got ' . $tokens[$tokenIndex]);
                }

                if ($result === null) {
                    $result = $this->compileScalarValue($var);
                }

                $filter = $this->parseFilter($tokens, $tokenIndex, $useOutputFilters);
                if ($filter) {
                    $filters[] = $filter;
                }
            } while (isset($tokens[$tokenIndex]));
        }

        $result = $this->applyFilters($result, $filters, $isLogic, $useOutputFilters);

        if ($isNested) {
            $result = '(' . $result . ')';
        }

        return $result;
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
     * @param boolean $isLogic Flag to see if the result is parsed for output.
     * Set to false to display the result, true is considered template logic.
     * @return string Compiled value
     */
    private function compileVariable($variable, $isLogic) {
        $filters = [];
        $useOutputFilters = true;

        try {
            $name = $this->parseName($variable);

            return $this->applyFilters($this->compileGetVariable($name), $filters, $isLogic, $useOutputFilters);
        } catch (CompileTemplateException $exception) {
            // no simple variable, further code will compile
        }

        $tokens = $this->variableTokenizer->tokenize($variable);

        // parse filters and advanced assignments
        $result = null;
        $nested = '';
        $nestedLevel = 0;
        $array = '';
        $arrayLevel = 0;
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
                        if (mb_substr($value, 0, 1) !== SyntaxSymbol::VARIABLE_SEPARATOR) {
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

                        $result = '$context->ensureObject(' . $result . ', \'Could not call ' . mb_substr($variable, mb_strlen($expression) + 1) . ': ' . $expression . ' is not an object\')';
                        $result .= '->' . mb_substr($value, 1) . '(' . $arguments . ')';
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

                            $result = '$context->ensureObject(' . $this->compileGetVariable($name) . ', \'Could not call ' . mb_substr($variable, mb_strlen($name) + 2) . ': $' . $name . ' is not an object\')';
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
            } elseif ($token === SyntaxSymbol::FILTER) {
                $filter = $this->parseFilter($tokens, $tokenIndex, $useOutputFilters);
                if ($filter) {
                    $filters[] = $filter;
                }

                continue;
            }

            $value .= $token;

            $tokenIndex++;
        }

        // apply filters and output
        if ($result === null) {
            $result = $this->compileGetVariable($this->parseName($value));
        }

        $result = $this->applyFilters($result, $filters, $isLogic, $useOutputFilters);

        return $result;
    }


    public function compileFilters($expression, $filterExpression, $isLogic = false) {
        $filters = [];

        $filterExpression = SyntaxSymbol::FILTER . $filterExpression;
        $useOutputFilters = !$isLogic;

        $tokens = $this->variableTokenizer->tokenize($filterExpression);
        $tokenIndex = 0;

        do {
            if ($tokens[$tokenIndex] === SyntaxSymbol::FILTER) {
                $filter = $this->parseFilter($tokens, $tokenIndex, $useOutputFilters);
                if ($filter) {
                    $filters[] = $filter;
                }

                continue;
            } else {
                throw new CompileTemplateException('Invalid syntax: ' . $filterExpression);
            }
        } while (isset($tokens[$tokenIndex]));

        return $this->applyFilters($expression, $filters, $isLogic, $useOutputFilters);
    }

    /**
     * Compiles the filter tokens
     * @param array $tokens Tokens of the variable or value tokenizer
     * @param integer $tokenIndex Current token index, pipe symbol
     * @return string Compiled arguments for the applyFilters context function
     * @see TemplateContext
     */
    private function parseFilter(array $tokens, &$tokenIndex, &$useOutputFilters) {
        $tokenIndex++;
        if ($tokenIndex == count($tokens)) {
            throw new CompileTemplateException(implode('', $tokens) . ' could not be parsed: name of a filter expected after ' . SyntaxSymbol::FILTER);
        }

        $filter = '';
        $arguments = '';
        $value = '';
        $nested = 0;

        do {
            $token = $tokens[$tokenIndex];

            if (is_array($token)) {
                $value .= $this->parseNested($token);
            } elseif ($token == SyntaxSymbol::NESTED_OPEN && !$filter) {
                if (!$value) {
                    throw new CompileTemplateException(implode('', $tokens) . ' could not be parsed: name of a filter expexted after ' . SyntaxSymbol::FILTER);
                }

                $filter = $value;
                $value = '';
            } elseif ($token == SyntaxSymbol::NESTED_OPEN) {
                $nested++;
            } elseif ($token == SyntaxSymbol::NESTED_CLOSE && $nested != 0) {
                $nested--;
            } elseif ($token == SyntaxSymbol::NESTED_CLOSE) {
                $arguments = $value;
                $value = '';
            } elseif ($arguments) {
                throw new CompileTemplateException(implode('', $tokens) . ' could not be parsed: ' . SyntaxSymbol::FILTER . ' expected');
            } else {
                $value .= $token;
            }

            $tokenIndex++;
        } while (isset($tokens[$tokenIndex]) && ($tokens[$tokenIndex] !== SyntaxSymbol::FILTER && $tokens[$tokenIndex] !== ' '));

        if ($value && !$filter) {
            $filter = $value;
        } elseif ($value) {
            throw new CompileTemplateException(implode('', $tokens) . ' could not be parsed: ' . SyntaxSymbol::NESTED_CLOSE . ' expected');
        }

        if ($filter == SyntaxSymbol::OUTPUT_RAW) {
            $useOutputFilters = false;

            return false;
        }

        $result = '[';
        $result .= var_export($filter, true);

        $arguments = $this->compileFunction($arguments);
        if ($arguments) {
            $result .= ', ' . $arguments;
        }

        $result .= ']';

        return $result;
    }

    private function applyFilters($expression, $filters, $isLogic, $useOutputFilters = true) {
        $outputFilters = $this->context->getOutputFilters();
        if ((!$outputFilters || !$useOutputFilters || $isLogic) && !$filters) {
            // nothing to be done here
            return $expression;
        } elseif ((!$outputFilters || $isLogic || !$useOutputFilters) && $filters) {
            // no output filter or we are inside logic
            return '$context->applyFilters(' . $expression . ', [' . implode(', ', $filters) . '])';
        }

        // parse output filters
        foreach ($outputFilters as $name => $outputFilterValues) {
            $outputFilter = '[';
            $isFirst = true;
            foreach ($outputFilterValues as $outputFilterValue) {
                if ($isFirst) {
                    $isFirst = false;
                    $outputFilter .= var_export($outputFilterValue, true);
                } elseif (is_string($outputFilterValue)) {
                    $outputFilter .= ', "' . addcslashes($outputFilterValue, '"$\\') . '"';
                } else {
                    $outputFilter .= ', ' . var_export($outputFilterValue, true);
                }
            }
            $outputFilter .= ']';

            if (!in_array($outputFilter, $filters)) {
                $outputFilters[$name] = $outputFilter;
            } else {
                unset($outputFilters[$name]);
            }
        }

        return '$context->applyFilters(' . $expression . ', [' . implode(', ', $filters + $outputFilters) . '])';
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
                    throw new CompileTemplateException('Invalid syntax ' . $signature);
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
                $result .= $this->compileExpression(trim($token));
            }
        }

        return $result;
    }

    /**
     * Parses and validates a scalar value being a number, boolean or string
     * @param string $value Value to parse
     * @param boolean $returnValue Set to true to trim string quotes
     * @return mixed Parsed value
     * @throws \huqis\exception\CompileTemplateException when the
     * provided value is not considered a scalar value
     */
    public function compileScalarValue($value, $returnValue = false) {
        if (is_numeric($value)) {
            return (double) $value;
        }

        if ($value === 'null' || $value === 'true' || $value === 'false') {
            return $value;
        }

        $firstChar = mb_substr($value, 0, 1);
        $lastChar = mb_substr($value, -1);

        // escape scalar string
        if ($firstChar === StringSymbol::SYMBOL && $lastChar === StringSymbol::SYMBOL) {
            if ($returnValue) {
                return mb_substr($value, 1, -1);
            } else {
                return '"' . addcslashes(mb_substr(str_replace('\\"', '"', $value), 1, -1), '"$\\') . '"';
            }
        } elseif ($firstChar === String2Symbol::SYMBOL && $lastChar === String2Symbol::SYMBOL) {
            if ($returnValue) {
                return mb_substr($value, 1, -1);
            } else {
                return "'" . addcslashes(mb_substr(str_replace("\\'", "'", $value), 1, -1), "'$\\") . "'";
            }
        }

        throw new CompileTemplateException('Invalid syntax ' . $value);
    }

    /**
     * Compiles a get variable call
     * @param string $name Name of the variable
     * @return string
     */
    private function compileGetVariable($name) {
        $suffix = '';

        if (strpos($name, SyntaxSymbol::VARIABLE_SEPARATOR) === false) {
            $suffix = ', null, false';
        }

        return '$context->getVariable(\'' . $name . '\'' . $suffix . ')';
    }

    /**
     * Applies the strict
     * @param string $output Compiled output of an expression
     * @param boolean $isLogic
     * @return string
     */
    private function compileOutput($output, $isLogic) {
        if (!$isLogic) {
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
     * @throws \huqis\exception\CompileTemplateException when the
     * provided name is not a valid variable or function name
     */
    public function parseName($name, $isVariable = true) {
        $firstChar = mb_substr($name, 0, 1);
        if (($isVariable && $firstChar !== '$') || (!$isVariable && $firstChar === '$')) {
            throw new CompileTemplateException('Invalid syntax ' . $name);
        }

        if ($isVariable) {
            $name = mb_substr($name, 1);
        }

        $nameLength = mb_strlen($name);
        for ($i = 0; $i < $nameLength; $i++) {
            $char = ord(mb_substr($name, $i, 1));
            if (($char >= 48 && $char <= 58) || ($char >= 65 && $char <= 91) || ($char >= 97 && $char <= 123) || $char == 95) { // 0-9 || A-Z || a-z || _
                continue;
            } elseif ($isVariable && $char == 46) { // .
                continue;
            }

            if ($isVariable) {
                throw new CompileTemplateException('Invalid syntax $' . $name);
            } else {
                throw new CompileTemplateException('Invalid syntax ' . $name);
            }
        }

        return $name;
    }

    /**
     * Parses nested syntax tokens back into a string
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
