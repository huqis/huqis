<?php

namespace huqis\tokenizer;

use PHPUnit\Framework\TestCase;

class ValueTokenizerTest extends TestCase {

	private $tokenizer;

	protected function setUp(): void {
		$this->tokenizer = new ValueTokenizer();
	}

    /**
     * @dataProvider providerTokenize
     */
	public function testTokenize($string, $expected) {
        $result = $this->tokenizer->tokenize($string);

        $this->assertEquals(array_values($result), $expected);
	}

    public static function providerTokenize() {
        return array(
            array(
                '"test"',
                array(
                    '"test"'
                ),
            ),
            array(
                '" "',
                array(
                    '" "'
                ),
            ),
            array(
                '"te\\"st"',
                array(
                    '"te\\"st"'
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
