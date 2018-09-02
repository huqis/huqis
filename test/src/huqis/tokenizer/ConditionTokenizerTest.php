<?php

namespace huqis\tokenizer;

use PHPUnit\Framework\TestCase;

class ConditionTokenizerTest extends TestCase {

	private $tokenizer;

	protected function setUp() {
		$this->tokenizer = new ConditionTokenizer();
        $this->tokenizer->setOperator(' and ');
        $this->tokenizer->setOperator(' or ');
        $this->tokenizer->setOperator('&&');
        $this->tokenizer->setOperator('||');
	}

    /**
     * @dataProvider providerTokenize
     */
	public function testTokenize($string, $expected) {
        $result = $this->tokenizer->tokenize($string);

        $this->assertEquals(array_values($result), $expected);
	}

    public function providerTokenize() {
        return array(
            array(
                '$value && $value2',
                array(
                    '$value ',
                    '&&',
                    ' $value2'
                ),
            ),
            array(
                '$value and $value2',
                array(
                    '$value',
                    ' and ',
                    '$value2'
                ),
            ),
            array(
                '$value and ($value2 or $value3)',
                array(
                    '$value',
                    ' and ',
                    array(
                        '$value2',
                        ' or ',
                        '$value3',
                    ),
                ),
            ),
            array(
                '(($value1.test() and $value2) or ($value2 and $value3)) || ($value5 and $value6)',
                array(
                    array(
                        array(
                            '$value1.test()',
                            ' and ',
                            '$value2',
                        ),
                        ' or ',
                        array(
                            '$value2',
                            ' and ',
                            '$value3',
                        ),
                    ),
                    '||',
                    array(
                        '$value5',
                        ' and ',
                        '$value6',
                    ),
                ),
            ),
        );
    }

}
