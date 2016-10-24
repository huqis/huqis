<?php

namespace frame\library;

use frame\library\exception\CompileTemplateException;

/**
 * Output buffer for the template compiler
 */
class TemplateOutputBuffer {

    /**
     * Flag to see if the current mode is PHP mode
     * @var boolean
     */
    protected $isPhp = true;

    /**
     * Flag to see the current output buffer has actual output
     * @var boolean
     */
    protected $hasOutput = false;

    /**
     * Flag to see if output is recorded
     * @var boolean
     */
    protected $recordOutput = true;

    /**
     * Contents of the current buffer
     * @var string
     */
    protected $buffer = '';

    /**
     * Extendable block buffers being processed
     * @var array
     */
    protected $buffers = array();

    /**
     * Name of the buffers to append indexed on key
     * @var array
     */
    protected $append = array();

    /**
     * Name of the buffers to prepend indexed on key
     * @var array
     */
    protected $prepend = array();

    /**
     * Gets the string representation of this buffer
     * @return string
     */
    public function __toString() {
        $buffer = $this->buffer;
        if (!$this->isPhp) {
            $buffer .= '<?php ';
        }

        return $buffer;
    }

    /**
     * Sets whether output is being recorded
     * @param boolean $recordOutput
     * @return null
     * @see recordsOutput
     * @see hasOutput
     */
    public function setRecordOutput($recordOutput) {
        $this->recordOutput = $recordOutput;
    }

    /**
     * Gets whether output is being recorded
     * @return boolea,
     * @see setRecordOutput
     * @see hasOutput
     */
    public function recordsOutput() {
        return $this->recordOutput;
    }

    /**
     * Checks if this buffer has recorded actual output, meaning text or echo
     * code statements
     * @return boolean
     * @see setRecordOutput
     * @see recordsOutput
     */
    public function hasOutput() {
        return $this->hasOutput;
    }

    /**
     * Appends plain unprocessable text to the buffer
     * @param string $text Text to append
     * @return null
     * @see appendCode
     */
    public function appendText($text) {
        if ($this->isPhp) {
            // switch to text mode
            $this->isPhp = false;
            $this->buffer .= ' ?>';
        }

        if ($this->recordOutput) {
            $this->hasOutput = true;
        }

        $this->buffer .= $text;
    }

    /**
     * Appends a piece of PHP code which needs interpretation to the buffer
     * @param string $code Code to append
     * @return null
     * @see appendText
     */
    public function appendCode($code) {
        if (!$this->isPhp) {
            // switch to php mode
            $this->isPhp = true;
            $this->buffer .= '<?php ';
        }

        if ($this->recordOutput && substr($code, 0, 5) == 'echo ') {
            $this->hasOutput = true;
        }

        $this->buffer .= $code;
    }

    /**
     * Starts a code block with a new child context
     * @return null
     * @see endCodeBlock
     */
    public function startCodeBlock() {
        $this->appendCode('{ $context = $context->createChild();');
    }

    /**
     * Stops the current code block and returns to the parent context
     * @param boolean $keepVariables Set to true to keep the variables from the
     * child context
     * @return null
     * @see startCodeBlock
     */
    public function endCodeBlock($keepVariables = false) {
        $this->appendCode('$context = $context->getParent(' . var_export($keepVariables, true) . '); }');
    }

    /**
     * Starts a sub output buffer to catch a subcompile result into a closure
     * @return null
     * @see endBufferBlock
     * @see \frame\library\block\AssignTemplateBlock
     * @see \frame\library\block\CallTemplateBlock
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
     * Starts an extendable block
     * @param string $name Name of the extendable block
     * @return null
     * @throws \frame\library\exception\CompileTemplateException when the name
     * of the block is already used by a parent block
     */
    public function startExtendableBlock($name) {
        if (isset($this->buffers[$name])) {
            throw new CompileTemplateException('Cannot start a block ' . $name . ': block has the same name as a parent block');
        }

        $this->buffers[$name] = array(
            'buffer' => $this->buffer,
            'isPhp' => $this->isPhp,
        );

        $this->buffer = '';
        $this->isPhp = false;
    }

    /**
     * Starts an extendable block but appends the contents to the contents in
     * the parent block
     * @param string $name Name of the extendable block
     * @return null
     */
    public function appendExtendableBlock($name) {
        $this->startExtendableBlock($name);

        $this->append[$name] = true;
    }

    /**
     * Starts an extendable block but prepends the contents to the contents in
     * the parent block
     * @param string $name Name of the extendable block
     * @return null
     */
    public function prependExtendableBlock($name) {
        $this->startExtendableBlock($name);

        $this->prepend[$name] = true;
    }

    /**
     * Ends an extendable block
     * @param string $name Name of the parent block
     * @throws \frame\library\exception\CompileTemplateException when the block
     * is not opened
     */
    public function endExtendableBlock($name) {
        if (!isset($this->buffers[$name])) {
            throw new CompileTemplateException('Cannot end block ' . $name . ': block is not opened');
        }

        $block = $this->buffer;
        if ($this->isPhp) {
            $block .= ' ?>';
        }

        // reset to parent buffer
        $this->buffer = $this->buffers[$name]['buffer'];
        $this->isPhp = $this->buffers[$name]['isPhp'];
        unset($this->buffers[$name]);

        // look for the block
        $open = '/*block-' . $name . '-start*/ ?>';
        $close = '<?php /*block-' . $name . '-end*/ ?>';

        $positionOpen = strpos($this->buffer, $open);
        $positionClose = strpos($this->buffer, $close);

        if ($positionOpen !== false && $positionClose !== false) {
            // block already exists
            if (isset($this->append[$name])) {
                // process to append to the parent block
                $start = $positionOpen + strlen($open);
                $parentBlock = substr($this->buffer, $start, $positionClose - $start);
                $block = $parentBlock . $block;

                unset($this->append[$name]);
            } elseif (isset($this->prepend[$name])) {
                // process to prepend to the parent block
                $start = $positionOpen + strlen($open);
                $parentBlock = substr($this->buffer, $start, $positionClose - $start);
                $block .= $parentBlock;

                unset($this->prepend[$name]);
            }

            // replace the existing block with the new block content
            $this->buffer = substr($this->buffer, 0, $positionOpen) . $open . $block . substr($this->buffer, $positionClose);
        } else {
            // new block, just append it
            $this->appendCode($open . $block . $close);

            // a block end with closing php, so turn it off
            $this->isPhp = false;
        }
    }

}
