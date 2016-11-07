<?php

namespace huqis\exception;

/**
 * Exception thrown when an error occurs while compiling a template
 */
class CompileTemplateException extends AbstractResourceTemplateException {

    /**
     * Line number of the error
     * @var integer
     */
    private $lineNumber;

    /**
     * Sets the line number of the error
     * @param integer $lineNumber Line number
     * @return null
     */
    public function setLineNumber($lineNumber) {
        $this->lineNumber = $lineNumber;
    }

    /**
     * Gets the line number of the error
     * @return integer|null Line number of the error if known, null otherwise
     */
    public function getLineNumber() {
        return $this->lineNumber;
    }

}
