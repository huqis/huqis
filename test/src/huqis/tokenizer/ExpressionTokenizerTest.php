<?php

namespace huqis\tokenizer;

use PHPUnit\Framework\TestCase;

class ExpressionTokenizerTest extends TestCase {

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
            array(
                '$variable = functionCall()',
                array(
                    '$variable ',
                    '=',
                    ' functionCall',
                    '(',
                    ')',
                ),
            ),
                    );
    }

}
