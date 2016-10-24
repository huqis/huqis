<?php

namespace frame\library\tokenizer;

use frame\library\tokenizer\symbol\NestedSymbol;
use frame\library\tokenizer\symbol\SimpleSymbol;

use \PHPUnit_Framework_TestCase;

class TokenizerTest extends PHPUnit_Framework_TestCase {

	private $tokenizer;

	protected function setUp() {
		$this->tokenizer = new Tokenizer();
        $this->tokenizer->setWillTrimTokens(true);
        $this->tokenizer->addSymbol(new SimpleSymbol('AND'));
        $this->tokenizer->addSymbol(new SimpleSymbol('OR'));
        $this->tokenizer->addSymbol(new NestedSymbol('(', ')', $this->tokenizer));
	}

	public function testInterpret() {
	    $this->assertTrue($this->tokenizer->willTrimTokens());
	    $this->assertEquals(array(), $this->tokenizer->tokenize(''));

		$condition = '{field} = %2%';
		$tokens = $this->tokenizer->tokenize($condition);
		$this->assertNotNull($tokens);
		$this->assertTrue(is_array($tokens), 'result is not an array');
		$this->assertTrue(count($tokens) == 1, 'result has not expected number of tokens');
		$this->assertEquals(array('{field} = %2%'), $tokens);
	}

	public function testInterpretWithConditionOperator() {
		$condition = '{field} = %2% AND {field2} <= %1%';
        $tokens = $this->tokenizer->tokenize($condition);
		$this->assertNotNull($tokens);
		$this->assertTrue(is_array($tokens), 'result is not an array');
		$this->assertTrue(count($tokens) == 3, 'result has not expected number of tokens');
		$this->assertEquals(array('{field} = %2%', 'AND', '{field2} <= %1%'), $tokens);
	}

    public function testInterpretWithBrackets() {
        $condition = '{field} = %2% AND ({field2} <= %1% OR {field2} <= %2%)';
        $tokens = $this->tokenizer->tokenize($condition);
        $this->assertNotNull($tokens);
        $this->assertTrue(is_array($tokens), 'result is not an array');
        $this->assertTrue(count($tokens) == 3, 'result has not expected number of tokens');
        $this->assertEquals(array('{field} = %2%', 'AND', array('{field2} <= %1%', 'OR', '{field2} <= %2%')), $tokens);
    }

    public function testInterpretWithBracketsAtTheBeginning() {
        $condition = '({field2} <= %1% OR {field2} <= %2%) AND {field} = %2%';
        $tokens = $this->tokenizer->tokenize($condition);
        $this->assertNotNull($tokens);
        $this->assertTrue(is_array($tokens), 'result is not an array');
        $this->assertEquals(array(array('{field2} <= %1%', 'OR', '{field2} <= %2%'), 'AND', '{field} = %2%'), $tokens);
    }

    public function testInterpretWithMultipleNestedBrackets() {
        $condition = '{field} = 5 AND (({field2} <= %1%) OR ({field2} >= %2%))';
        $tokens = $this->tokenizer->tokenize($condition);
        $this->assertNotNull($tokens);
        $this->assertTrue(is_array($tokens), 'result is not an array');
        $this->assertTrue(count($tokens) == 3, 'result has not expected number of tokens');
        $this->assertEquals(array('{field} = 5', 'AND', array(array('{field2} <= %1%'), 'OR', array('{field2} >= %2%'))), $tokens);
    }

}
