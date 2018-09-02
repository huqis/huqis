<?php

namespace huqis;

use huqis\resource\ArrayTemplateResourceHandler;

use PHPUnit\Framework\TestCase;

class TemplateContextTest extends TestCase {

    public function providerSetVariable() {
        return array(
            array('variable', 3, array('variable' => null)),
            array('variable.test', 3, array()),
            array('variable.test', 3, array('variable' => array('test' => 1))),
            array('variable.context.property', 3, array('variable' => array('test' => 1))),
            array('variable.context.property2', 7, array('variable' => array('context' => array('property' => 3)))),
        );
    }

    /**
     * @dataProvider providerSetVariable
     */
    public function testSetVariable($variable, $value, $variables) {
        $context = new TemplateContext(new ArrayTemplateResourceHandler());
        $context->setVariables($variables);

        $context->setVariable($variable, $value);

        $this->assertEquals($value, $context->getVariable($variable));
    }

}
