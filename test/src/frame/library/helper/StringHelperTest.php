<?php

namespace frame\library\helper;

use \Exception;
use \PHPUnit_Framework_TestCase;

class StringHelperTest extends PHPUnit_Framework_TestCase {

    public function testGenerate() {
        $string = StringHelper::generate();

        $this->assertNotNull($string);
        $this->assertTrue(!empty($string));
        $this->assertTrue(strlen($string) == 8);
    }

    public function testGenerateThrowsExceptionWhenLengthOfHaystackIsLessThenRequestedLength() {
        try {
            StringHelper::generate(155);
        } catch (Exception $e) {
            return;
        }

        $this->fail();
    }

    public function testGenerateThrowsExceptionWhenInvalidLengthProvided() {
        try {
            StringHelper::generate('test');
        } catch (Exception $e) {
            return;
        }

        $this->fail();
    }

    public function testGenerateThrowsExceptionWhenInvalidHaystackProvided() {
        try {
            StringHelper::generate(8, $this);
        } catch (Exception $e) {
            return;
        }

        $this->fail();
    }

    /**
     * @dataProvider providerStartsWith
     */
    public function testStartsWith($expected, $value, $start, $isCaseInsensitive) {
        $result = StringHelper::startsWith($value, $start, $isCaseInsensitive);

        $this->assertEquals($expected, $result);
    }

    public function providerStartsWith() {
        return array(
            array(true, 'This is Joe', 'This', false),
            array(false, 'This is joe', 'this', false),
            array(false, 'Thi', 'this', false),
            array(false, 'This is Joe', array('no', 'yes'), false),
            array(true, 'This is Joe', array('no', 'This'), false),
            array(false, 'This is Joe', array('no', 'this'), false),
            array(true, 'This is Joe', array('no', 'this'), true),
        );
    }

    /**
     * @dataProvider providerTruncate
     */
    public function testTruncate($expected, $value, $length, $etc, $breakWords) {
        $result = StringHelper::truncate($value, $length, $etc, $breakWords);

        $this->assertEquals($expected, $result);
    }

    public function providerTruncate() {
        return array(
            array('', '', 9, '...', false),
            array('Let\'s...', 'Let\'s create a stràngé STRING', 12, '...', false),
            array('Let\'s cre...', 'Let\'s create a stràngé STRING', 12, '...', true),
        );
    }

    /**
     * @dataProvider providerTruncateThrowsExceptionWhenInvalidArgumentProvided
     */
    public function testTruncateThrowsExceptionWhenInvalidArgumentProvided($length, $etc) {
        $string = 'abcdefghijklmnopqrstuvwxyz';

        try {
            StringHelper::truncate($string, $length, $etc);
        } catch (Exception $e) {
            return;
        }

        $this->fail();
    }

    public function providerTruncateThrowsExceptionWhenInvalidArgumentProvided() {
        return array(
            array(array(), '...'),
            array($this, '...'),
            array(-2, '...'),
            array(0, '...'),
            array(1, array()),
            array(1, $this),
        );
    }

    /**
     * @dataProvider providerSafeString
     */
    public function testSafeString($expected, $value) {
        $locale = setlocale(LC_ALL, 'en_IE.utf8', 'en_IE', 'en');

        $result = StringHelper::safeString($value);

        $this->assertEquals($expected, $result);
    }

    public function providerSafeString() {
        return array(
            array('', ''),
            array('simple-test', 'Simple test'),
            array('internet-explorer-pocket', 'Internet Explorer (Pocket)'),
            array('jefs-book', 'Jef\'s book'),
            array('test', '##tEst@|"'),
            array('a-image.jpg', 'a-image.jpg'),
            array('lets-test-with-some-strange-chars', 'Let\'s test with some stràngé chars'),
            array('categorien', 'Categoriën'),
        );
    }

}
