<?php

namespace huqis;

use huqis\exception\CompileTemplateException;

/**
 * Output buffer for the template compiler
 */
class TemplateOutputBuffer {

    /**
     * Comment to mark the start of an extends block
     * @var string
     */
    const EXTENDS_START = '/*extends-start*/';

    /**
     * Comment to mark the end of an extends block
     * @var string
     */
    const EXTENDS_END = '/*extends-end*/';

    /**
     * Comment to mark the start of a block
     * @var string
     */
    const BLOCK_START = '/*block-%name%-start*/';

    /**
     * Comment to mark the end of a block
     * @var string
     */
    const BLOCK_END = '/*block-%name%-end*/';

    /**
     * Append strategy for extendable blocks
     * @var string
     */
    const STRATEGY_APPEND = 'append';

    /**
     * Prepend strategy for extendable blocks
     * @var string
     */
    const STRATEGY_PREPEND = 'prepend';

    /**
     * Replace strategy for extendable blocks, default
     * @var string
     */
    const STRATEGY_REPLACE = 'replace';

    /**
     * Contents of the current buffer
     * @var string
     */
    private $buffer = '';

    /**
     * Extendable block buffers being processed
     * @var array
     */
    private $buffers = array();

    /**
     * Name of the defined blocks
     * @var array
     */
    private $blocks = [];

    /**
     * Stack of the block elements
     * @var array
     */
    private $blockStack = [];

    /**
     * Flag to see if output is allowed
     * @var boolean
     */
    private $allowOutput = true;

    /**
     * Stack for the history of the $allowOutput flag
     * @var array
     */
    private $allowOutputStack = [];

    /**
     * Indentation level
     * @var integer
     */
    private $indentLevel = 0;

    /**
     * Indentation string
     * @var string
     */
    private $indentString = '    ';

    /**
     * Gets the string representation of this buffer
     * @return string
     */
    public function __toString() {
        return $this->buffer;
    }

    /**
     * Checks if the output is part of a specific block
     * @param string $name Name of the parent block
     * @return boolean True when one of the parent blocks is the provided block,
     * false otherwise
     */
    public function hasParentBlock($name) {
        return in_array($name, $this->blockStack);
    }

    /**
     * Pushes a block to the block stack
     * $param string $name Name of the block
     * @return null
     */
    public function pushToBlockStack($name) {
        $this->blockStack[] = $name;
    }

    /**
     * Pops the last element from the block stack
     * @return string Name of the block
     */
    public function popFromBlockStack() {
        return array_pop($this->blockStack);
    }

    /**
     * Sets whether output is allowed
     * @param boolean $allowOutput
     * @return null
     * @see allowOutput
     */
    public function setAllowOutput($allowOutput) {
        $this->allowOutputStack[] = $this->allowOutput;
        $this->allowOutput = $allowOutput;
    }

    /**
     * Clears the last set allow output
     * @return null
     */
    public function clearAllowOutput() {
        $this->allowOutput = array_pop($this->allowOutputStack);
    }

    /**
     * Checks if this buffer allows actual output, meaning text or echo
     * code statements
     * @return boolean
     * @see setAllowOutput
     * @see clearAllowOutput
     */
    public function allowOutput() {
        return $this->hasOutput;
    }

    /**
     * Sets the indentation level
     * @param integer $indent
     * @return null
     */
    public function setIndent($indent) {
        $this->indentLevel = $indent;
    }

    /**
     * Gets the indentation string for the current indentation level
     * @return string
     */
    private function getIndentation() {
        $indentation = '';
        for ($i = 0; $i < $this->indentLevel; $i++) {
            $indentation .= $this->indentString;
        }

        return $indentation;
    }

    /**
     * Appends the current level of indentation to the buffer
     * @return null
     */
    private function appendIndentation() {
        $this->buffer .= $this->getIndentation();
    }

    /**
     * Appends plain unprocessable text to the buffer
     * @param string $text Text to append
     * @return null
     * @see appendCode
     */
    public function appendText($text) {
        if (!$this->allowOutput && trim($text)) {
            throw new CompileTemplateException('Output is not allowed in block ' . end($this->blockStack));
        }

        if ($text == '') {
            return;
        }

        $this->appendIndentation();
        $this->buffer .= 'echo "' . str_replace("\n", '\\n', addcslashes($text, '"$\\')) . '";' . "\n";
    }

    /**
     * Appends a line of PHP code which needs interpretation
     * @param string $code Code to append
     * @return null
     * @see appendText
     */
    public function appendCode($code) {
        if (trim($code) == '') {
            return;
        }

        $isOutput = substr($code, 0, 5) == 'echo ';
        if (!$this->allowOutput && $isOutput) {
            throw new CompileTemplateException('Output is not allowed in block ' . end($this->blockStack));
        }

        $firstChar = substr($code, 0, 1);
        $lastChar = substr($code, -1);
        $last2Chars = substr($code, -2);

        if ($lastChar === '}' || ($lastChar == '{' && $firstChar == '}') || $last2Chars === '};' || $last2Chars === '];' || ($firstChar === ']' && $last2Chars == ');' && substr($code, 0, 4) !== 'echo')) {
            $this->indentLevel--;
        }

        $this->appendIndentation();
        $this->buffer .= $code;
        $this->buffer .= "\n";

        if (($lastChar === '{') || $lastChar === '[') {
            $this->indentLevel++;
        }
    }

    /**
     * Starts a code block with a new child context
     * @return null
     * @see endCodeBlock
     */
    public function startCodeBlock() {
        // $this->appendCode('{');
        $this->appendCode('$context = $context->createChild();');
    }

    /**
     * Stops the current code block and returns to the parent context
     * @param boolean $keepVariables Set to true to keep the variables from the
     * child context
     * @return null
     * @see startCodeBlock
     */
    public function endCodeBlock($keepVariables = false) {
        $this->appendCode('$context = $context->getParent(' . var_export($keepVariables, true) . ');');
        // $this->appendCode('}');
    }

    /**
     * Starts a sub output buffer to catch a subcompile result into a closure
     * @return null
     * @see endBufferBlock
     * @see \huqis\block\AssignTemplateBlock
     * @see \huqis\block\CallTemplateBlock
     */
    public function startBufferBlock() {
        $this->startCodeBlock();

        $this->appendCode('ob_start();');
        $this->appendCode('try {');
    }

    /**
     * Stops the current sub output buffer
     * @var string $variable Name of the output variable
     * @return null
     * @see startBufferBlock
     */
    public function endBufferBlock() {
        $this->appendCode('} catch (\Exception $exception) {');
        $this->appendCode('ob_end_clean();');
        $this->appendCode('throw $exception;');
        $this->appendCode('}');

        $this->endCodeBlock(false);

        $this->appendCode('$output = ob_get_contents();');
        $this->appendCode('ob_end_clean();');
        $this->appendCode('return $output;');
    }

    /**
     * Starts an extends block
     * @return null
     */
    public function startExtends() {
        $this->appendCode(self::EXTENDS_START);
    }

    /**
     * Ends an extends block
     * @return null
     */
    public function endExtends() {
        $this->appendCode(self::EXTENDS_END);

        $this->clearAllowOutput();
    }

    /**
     * Starts an extendable block
     * @param string $name Name of the extendable block
     * @return null
     * @throws \huqis\exception\CompileTemplateException when the name
     * of the block is already used by a parent block
     */
    public function startExtendableBlock($name) {
        if (isset($this->buffers[$name])) {
            throw new CompileTemplateException('Cannot start a block ' . $name . ': block has the same name as a parent block');
        }

        $this->buffers[$name] = $this->buffer;

        $this->buffer = '';
    }

    /**
     * Ends an extendable block
     * @param string $name Name of the parent block
     * @param string $strategy Strategy to handle the block, can be replace, append
     * or prepend
     * @throws \huqis\exception\CompileTemplateException when the block
     * is not opened or an invalid strategy provided
     */
    public function endExtendableBlock($name, $strategy = self::STRATEGY_REPLACE) {
        if (!isset($this->buffers[$name])) {
            throw new CompileTemplateException('Cannot end block ' . $name . ': block is not opened');
        } elseif ($strategy != self::STRATEGY_APPEND && $strategy != self::STRATEGY_PREPEND && $strategy != self::STRATEGY_REPLACE) {
            throw new CompileTemplateException('Cannot end block ' . $name . ': invalid strategy provided, try ' . self::STRATEGY_APPEND . ', ' . self::STRATEGY_PREPEND . ' or ' . self::STRATEGY_REPLACE);
        }

        $this->blocks[$name] = true;

        $block = $this->buffer;

        // reset to parent buffer
        $this->buffer = $this->buffers[$name];
        unset($this->buffers[$name]);

        // look for the block
        $commentOpen = str_replace('%name%', $name, self::BLOCK_START);
        $commentClose = str_replace('%name%', $name, self::BLOCK_END);

        $extendsPosition = $this->getExtendsPosition($this->buffer);
        if ($extendsPosition === false) {
            $offset = 0;
        } else {
            $offset = $extendsPosition;
        }

        $positionOpen = strpos($this->buffer, $commentOpen, $offset);
        $positionClose = strpos($this->buffer, $commentClose, $offset);

        if ($positionOpen !== false && $positionClose !== false) {
            // block already exists
            if ($strategy == self::STRATEGY_APPEND) {
                // process to append to the parent block
                $start = $positionOpen + strlen($commentOpen . "\n");
                $parentBlock = substr($this->buffer, $start, $positionClose - $start);
                $block = $parentBlock . $block;
            } elseif ($strategy == self::STRATEGY_PREPEND) {
                // process to prepend to the parent block
                $start = $positionOpen + strlen($commentOpen . "\n");
                $parentBlock = substr($this->buffer, $start, $positionClose - $start);
                $block .= $parentBlock;
            }

            // replace the existing block with the new block content
            $this->buffer = substr($this->buffer, 0, $positionOpen) . $commentOpen . "\n" . $block . $this->getIndentation() . substr($this->buffer, $positionClose);
        } else {
            // new block

            // no output allowed
            if (!$this->allowOutput) {
                throw new CompileTemplateException('Cannot extend block ' . $name . ': not defined in extended template');
            }

            if (substr($block, -1) == "\n") {
                $block = substr($block, 0, -1);
            }

            // we're cool,
            $this->appendCode($commentOpen);
            $this->appendCode($block);
            $this->appendCode($commentClose);
        }
    }

    /**
     * Gets the position of the open extends
     * @param string $string String to look in
     * @return integer Position of the open extends
     * @throws \ride\library\tokenizer\exception\TokenizeException when the symbol is opened but not closed
     */
    protected function getExtendsPosition($string) {
        // look for first close
        $startPosition = strrpos($string, self::EXTENDS_START);

        // look for another open between initial open and close
        $endPosition = strrpos($string, self::EXTENDS_END);
        if ($endPosition === false || $endPosition < $startPosition) {
            return $startPosition;
        }

        $startEndPosition = $this->getExtendsPosition(substr($string, 0, $endPosition));

        return $this->getExtendsPosition(substr($string, 0, $startEndPosition));
    }

}
