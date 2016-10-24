<?php

namespace frame\library\operator\logical;

/**
 * Interface for a logical operator like AND, OR, ...
 */
interface LogicalOperator {

    /**
     * Gets the PHP equivalent of this operator
     * @return string PHP operator
     */
    public function getOperator();

}
