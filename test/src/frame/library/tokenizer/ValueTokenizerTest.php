<?php

namespace frame\library\tokenizer;

use \PHPUnit_Framework_TestCase;

class ValueTokenizerTest extends PHPUnit_Framework_TestCase {

	private $tokenizer;

	protected function setUp() {
		$this->tokenizer = new ValueTokenizer();
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
                '"test"',
                array(
                    '"',
                    'test',
                    '"'
                ),
            ),
            array(
                '"te\\"st"',
                array(
                    '"',
                    'te\\"st',
                    '"'
                ),
            ),
            array(
                'functionCall()',
                array(
                    'functionCall',
                    '(',
                    ')',
                ),
            ),
        );
    }

}
