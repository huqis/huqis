<?php

namespace huqis\operator\logical;

/**
 * Generic logical operator
 */
class GenericLogicalOperator implements LogicalOperator {

    /**
     * Compiled operator
     * @var string
     */
    private $operator;

    /**
     * Constructs a new generic operator
     * @param string $operator Operator in the PHP language
     * @return null
     */
    public function __construct($operator) {
        $this->operator = $operator;
    }

    /**
     * Gets the PHP equivalent of this operator
     * @return string PHP operator
     */
    public function getOperator() {
        return $this->operator;
    }

}
