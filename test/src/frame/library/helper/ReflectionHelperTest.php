<?php

namespace frame\library\helper;

use \PHPUnit_Framework_TestCase;

class ReflectionHelperTest extends PHPUnit_Framework_TestCase {

    /**
     * @var ReflectionHelper
     */
    private $helper;

    public $sme = 7;

    private $pla = 5;

    protected function setUp() {
        $this->helper = new ReflectionHelper();
    }

    public function testGetProperty() {
        // with getter
        $result = $this->helper->getProperty($this, 'helper');

        $this->assertTrue($result === $this->helper);

        // public variable
        $result = $this->helper->getProperty($this, 'sme');

        $this->assertTrue($result === 7);

        // private variable
        $result = $this->helper->getProperty($this, 'pla', null);

        $this->assertTrue($result === null);

        // array
        $data = array(
            'sme' => $this->sme,
            'sub' => array(
                'sme' => $this->sme,
            ),
        );

        $result = $this->helper->getProperty($data, 'sme');
        $this->assertTrue($result === 7);

        $result = $this->helper->getProperty($data, 'sub[sme]');
        $this->assertTrue($result === 7);
    }

    public function testGetPropertyReturnsDefaultValue() {
        $default = 42;

        $result = $this->helper->getProperty($this, 'unexistant', $default);
        $this->assertTrue($result === $default);

        $data = array('key' => array('sub' => 'value'));

        $result = $this->helper->getProperty($data, 'unexistant', $default);
        $this->assertEquals($default, $result);

        $result = $this->helper->getProperty($data, 'key[unexistant]', $default);
        $this->assertEquals($default, $result);
    }

    /**
     * @dataProvider providerGetPropertyThrowsExceptionWhenInvalidNameProvided
     * @expectedException frame\library\exception\ReflectionTemplateException
     */
    public function testGetPropertyThrowsExceptionWhenInvalidNameProvided($name) {
        $data = array('test' => 'value');
        $this->helper->getProperty($data, $name);
    }

    public function providerGetPropertyThrowsExceptionWhenInvalidNameProvided() {
        return array(
        	array(array()),
            array($this),
            array('[test]'),
            array('test[test[test]]'),
            array('test[test]test'),
        );
    }

    public function testSetProperty() {
        $property = 'value';
        $value = 7;

        $this->assertFalse(isset($this->$property));

        $this->helper->setProperty($this, $property, $value);

        $this->assertTrue(isset($this->$property));
        $this->assertTrue($this->$property === $value);

        unset($this->$property);

        $this->assertFalse(isset($this->$property));

        // with setter
        $property = 'value';

        $this->helper->setProperty($this, 'dummy', $value);

        $this->assertTrue(isset($this->$property));
        $this->assertTrue($this->$property === $value);

        $data = array('sub' => array('value' => $value));

        $this->helper->setProperty($data, 'dummy', $value);
        $this->helper->setProperty($data, 'dummy2[sub]', $value);
        $this->helper->setProperty($data, 'sub[sub2][sub3]', $value);

        $this->assertTrue($data['dummy'] === $value);
        $this->assertTrue($data['dummy2']['sub'] === $value);
        $this->assertTrue($data['sub']['sub2']['sub3'] === $value);
    }

    /**
     * @dataProvider providerGetPropertyThrowsExceptionWhenInvalidNameProvided
     * @expectedException frame\library\exception\ReflectionTemplateException
     */
    public function testSetPropertyThrowsExceptionWhenInvalidNameProvided($name) {
        $data = array('test' => 'value');
        $this->helper->setProperty($data, $name, 'value');
    }

    public function getHelper() {
        return $this->helper;
    }

    public function setDummy($value) {
        $this->value = $value;
    }

}
