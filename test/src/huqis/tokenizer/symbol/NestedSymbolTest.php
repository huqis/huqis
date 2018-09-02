<?php

namespace huqis\tokenizer\symbol;

use PHPUnit\Framework\TestCase;

class NestedSymbolTest extends TestCase {

	/**
     * @dataProvider provideTokenize
	 */
	public function testTokenize($expected, $expectedProcess, $process, $toProcess, $allowsSymbolsBeforeOpen = true, $open = '(', $close = ')', $escape = null) {
		$symbol = new NestedSymbol($open, $close, null, false, $allowsSymbolsBeforeOpen);
        if ($escape) {
            $symbol->setEscapeSymbol($escape);
        }

        $result = $symbol->tokenize($process, $toProcess);

		$this->assertEquals($expected, $result);
		$this->assertEquals($expectedProcess, $process);
	}

	public function provideTokenize() {
	    return array(
	       array(null, 'test', 'test', 'test and test'),
	       array(array('yes ', 'test and test'), 'yes (test and test)', 'yes (', 'yes (test and test)'),
	       array(array('yes ', 'test (and test)'), 'yes (test (and test))', 'yes (', 'yes (test (and test))'),
	       array(null, 'yes (', 'yes (', 'yes (test (and test))', false),
	       array(array('yes ', 'test and test'), 'yes "test and test"', 'yes "', 'yes "test and test" and "test"', true, '"', '"'),
	       array(array('te\\"st'), '"te\\"st"', '"', '"te\\"st"', false, '"', '"', '\\'),
   	    );
	}

}
