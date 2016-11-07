<?php

namespace huqis\tokenizer\symbol;

use \PHPUnit_Framework_TestCase;

class SimpleSymbolTest extends PHPUnit_Framework_TestCase {

	/**
     * @dataProvider provideTokenize
	 */
	public function testTokenize($expected, $process, $toProcess, $willIncludeSymbols) {
		$symbol = new SimpleSymbol('AND', $willIncludeSymbols);

        $result = $symbol->tokenize($process, $toProcess);

		$this->assertEquals($expected, $result);
	}

	public function provideTokenize() {
	    return array(
	       array(array('test', 'AND'), 'testAND', 'testANDtest', true),
	       array(null, 'test', 'testANDtest', true),
	       array(array('AND'), 'AND', 'ANDtest', true),
	       array(array('test'), 'testAND', 'testANDtest', false),
	       array(null, 'test', 'testANDtest', false),
	       array(array(), 'AND', 'ANDtest', false),
	    );
	}

}
