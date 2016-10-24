<?php

namespace frame\library\tokenizer;

use \PHPUnit_Framework_TestCase;

class ExpressionTokenizerTest extends PHPUnit_Framework_TestCase {

	private $tokenizer;

	protected function setUp() {
		$this->tokenizer = new ExpressionTokenizer();
        $this->tokenizer->setOperator('+');
        $this->tokenizer->setOperator('-');
        $this->tokenizer->setOperator('<');
        $this->tokenizer->setOperator('==');
        $this->tokenizer->setOperator('=');
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
                '$value == $value2',
                array(
                    '$value ',
                    '==',
                    ' $value2',
                ),
            ),
            array(
                '$value = $value2 == $value3',
                array(
                    '$value ',
                    '=',
                    ' $value2 ',
                    '==',
                    ' $value3',
                ),
            ),
            array(
                '$variable = 5 < 10',
                array(
                    '$variable ',
                    '=',
                    ' 5 ',
                    '<',
                    ' 10',
                ),
            ),
        );
    }

}
