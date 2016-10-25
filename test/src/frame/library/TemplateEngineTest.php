<?php

namespace frame\library;

use frame\library\resource\ArrayTemplateResourceHandler;

use \PHPUnit_Framework_TestCase;

class TemplateEngineTest extends PHPUnit_Framework_TestCase {

    public function providerRender() {
        return array(
            array(
                'plain text',
                array(
                    'index' => 'plain text',
                ),
            ),
            array(
                "using a bracket {without closing",
                array(
                    'index' => "using a bracket {without closing",
                ),
            ),
            array(
                "using a bracket { with a space",
                array(
                    "index" => "using a bracket { with a space",
                ),
            ),
            array(
                "using a bracket { with a space, and close it }",
                array(
                    'index' => "using a bracket { with a space, and close it }"
                ),
            ),
            array(
                "using a bracket{\nand new line",
                array(
                    'index' => "using a bracket{\nand new line"
                ),
            ),
            // variables
            array(
                '"test"',
                array(
                    'index' => '"test"',
                ),
            ),
            array(
                '"te\\"\nst"',
                array(
                    'index' => '"te\\"\nst"',
                ),
            ),
            array(
                '{ $no} space after open bracket',
                array(
                    'index' => '{ $no} space after open bracket'
                ),
            ),
            array(
                'open close brackets straight after each other {}',
                array(
                    'index' => 'open close brackets straight after each other {}',
                ),
            ),
            array(
                'using a close bracket } without opening',
                array(
                    'index' => 'using a close bracket } without opening',
                ),
            ),
            array(
                'usage of a plain value..',
                array(
                    'index' => 'usage of a plain {$variable}..',
                ),
                array(
                    'variable' => 'value',
                ),
            ),
            array(
                'value begins the syntax',
                array(
                    'index' => '{$variable} begins the syntax',
                ),
                array(
                    'variable' => 'value',
                ),
            ),
            array(
                'ending with value',
                array(
                    'index' => 'ending with {$variable}',
                ),
                array(
                    'variable' => 'value',
                ),
            ),
            array(
                '',
                array(
                    'index' => '{* comment *}',
                ),
            ),
            array(
                '',
                array(
                    'index' => '{* {if $varaieble} *}',
                ),
            ),
            array(
                '<h1>My Cool Title</h1><h2>My cool title</h2>',
                array(
                    'index' => '<h1>{$title|capitalize}</h1><h2>{$title|capitalize:"first"}</h2>',
                ),
                array(
                    'title' => 'my cool title',
                ),
            ),
            // if test
            array(
                '2',
                array(
                    'index' => '{if $value == 1}{$value = $value + 1}{else}{$value = $value - 1}{/if}{$value}',
                ),
                array(
                    'value' => 1,
                ),
            ),
            array(
                '1',
                array(
                    'index' => '{if $value == 1}{$value = $value + 1}{else}{$value = $value - 1}{/if}{$value}',
                ),
                array(
                    'value' => 2,
                ),
            ),
            // foreach test
            array(
                '<ul><li>1 / 3: John [F]</li><li>2 / 3: Jane </li><li>3 / 3: Mike [L]</li></ul>',
                array(
                    'index' => '<ul>{foreach $names as $name loop $loop}<li>{$loop.index + 1} / {$loop.length}: {$name} {if $loop.first}[F]{elseif $loop.last}[L]{/if}</li>{/foreach}</ul>',
                ),
                array(
                    'names' => array('John', 'Jane', 'Mike'),
                ),
            ),
            // format tests
            array(
                '15.99 15.98765 Tue Oct 25 08:26:16 2016 2016-10-25',
                array(
                    'index' => '{$value = 15.987654321}{$value|format:"number"} {$value|format:"number":5} {$timestamp = 1477376776}{$timestamp|format:"date"} {$timestamp|format:"date":"%F"}',
                ),
            ),
            // cycle tests
            array(
                '<table><tr class="light"><td>John</td></tr><tr class="dark"><td>Jane</td></tr><tr class="light"><td>Mike</td></tr></table>',
                array(
                    'index' => '<table>{foreach $names as $name}<tr class="{cycle ["light", "dark"]}"><td>{$name}</td></tr>{/foreach}</table>',
                ),
                array(
                    'names' => array('John', 'Jane', 'Mike'),
                ),
            ),
            // macro tests
            array(
                'Hello, my name is Joe',
                array(
                    'index' => '{macro sayName($name)}{$name}{/macro}Hello, my name is {$echoName($name)}',
                ),
                array(
                    'echoName' => 'sayName',
                    'name' => 'Joe',
                ),
            ),
            array(
                '<p>Hello, my name is John</p><p>Hello, my name is Jane</p>',
                array(
                    'index' => '{macro sayName($name)}<p>Hello, my name is {$name}</p>{/macro}{foreach $names as $name}{sayName($name)}{/foreach}',
                ),
                array(
                    'names' => array('John', 'Jane'),
                ),
            ),
            array(
                '<p>Hello, my name is John</p><p>Hello, my name is Jane</p>',
                array(
                    'index' => '{macro sayName($name)}<p>Hello, my name is {$name}</p>{/macro}{include "include"}',
                    'include' => '{foreach $names as $name}{sayName($name)}{/foreach}',
                ),
                array(
                    'names' => array('John', 'Jane'),
                ),
            ),
            array(
                '<p>Hello, my name is John</p><p>Hello, my name is Jane</p>',
                array(
                    'index' => '{macro sayName($name)}<p>Hello, my name is {$name}</p>{/macro}{include $include}',
                    'include' => '{foreach $names as $name}{sayName($name)}{/foreach}',
                ),
                array(
                    'names' => array('John', 'Jane'),
                    'include' => 'include',
                ),
            ),
            array(
                '<p>Hello, my name is  </p>',
                array(
                    'index' => '{macro sayName($name, $test)}<p>Hello, my name is {$name} {$test}</p>{/macro}{sayName()}',
                ),
                array(),
            ),
            array(
                '19',
                array(
                    'index' => '{macro calculateSum($variable1, $variable2)}{return $variable1 + $variable2}{/macro}{$value1 = 7}{$value2 = 12}{$sum = calculateSum($value1, $value2)}{$sum}',
                ),
            ),
            // call tests
            array(
                '<p>Hello, my name is John and I\'m 30 years old.</p>',
                array(
                    'index' => '{macro sayName($name, $age)}<p>Hello, my name is {$name} and I\'m {$age} years old.</p>{/macro}{$age = 30}{call sayName($_call, $age)}{$name}{/call}',
                ),
                array(
                    'name' => 'John',
                ),
            ),
            array(
                '<p>Hello, my name is John and I\'m 30 years old.</p>',
                array(
                    'index' => '{macro sayName($name, $age)}<p>Hello, my name is {$name} and I\'m {$age} years old.</p>{/macro}{call sayName($_call, 30)}{$name}{/call}',
                ),
                array(
                    'name' => 'John',
                ),
            ),
            // assign tests
            array(
                '<p>Hello, my name is John and in 5 years, I\'m 35 years old.</p> 30',
                array(
                    'index' => '{$age = 30}{capture $hello}{$age = $age + 5}<p>Hello, my name is {$name} and in 5 years, I\'m {$age} years old.</p>{/capture}{$hello} {$age}',
                ),
                array(
                    'name' => 'John',
                ),
            ),
            // include tests
            array(
                'Header<h1>My Title</h1>Footer',
                array(
                    'index' => '{include "header"}<h1>{$title}</h1>{include "footer"}',
                    'header' => 'Header',
                    'footer' => 'Footer',
                ),
                array(
                    'title' => 'My Title',
                ),
            ),
            array(
                'Header<h1>My Title</h1>Footer',
                array(
                    'index' => '{include "helper" with ["value" = "Header"]}<h1>{$title}</h1>{include "helper" with ["value" = "Footer"]}',
                    'helper' => '{$value}',
                ),
                array(
                    'title' => 'My Title',
                ),
            ),
            // extends tests
            array(
                '<h1>My Title</h1>',
                array(
                    'index' => '{extends "base"}{block "title"}{$title}{/block}{/extends}',
                    'base' => '<h1>{block "title"}Default Title{/block}</h1>',
                ),
                array(
                    'title' => 'My Title',
                ),
            ),
            array(
                '<h1>Default Title - My Title</h1>',
                array(
                    'index' => '{extends "base"}{block "title" append} - {$title}{/block}{/extends}',
                    'base' => '<h1>{block "title"}Default Title{/block}</h1>',
                ),
                array(
                    'title' => 'My Title',
                ),
            ),
            array(
                '<h1>My Title - Default Title</h1>',
                array(
                    'index' => '{extends "base"}{block "title" prepend}{$title} - {/block}{/extends}',
                    'base' => '<h1>{block "title"}Default Title{/block}</h1>',
                ),
                array(
                    'title' => 'My Title',
                ),
            ),
            array(
                '<h1>Title - Section - Site</h1>',
                array(
                    'index' => '{extends "base2"}{block "title" prepend}{$subtitle} - {/block}{/extends}',
                    'base2' => '{extends "base"}{block "title" prepend}{$title} - {/block}{/extends}',
                    'base' => '<h1>{block "title"}Site{/block}</h1>',
                ),
                array(
                    'title' => 'Section',
                    'subtitle' => 'Title',
                ),
            ),
            array(
                '<h2>Heading 2: Section</h2>',
                array(
                    'index' => '{$base = "template-2"}{extends $base}{block "title" append}: {$title}{/block}{/extends}',
                    'template-1' => '<h1>{block "title"}Heading 1{/block}</h1>',
                    'template-2' => '<h2>{block "title"}Heading 2{/block}</h2>',
                ),
                array(
                    'title' => 'Section',
                ),
            ),

            // Nope, macros/functions should be defined before a block is first used
            // array(
                // array(
                    // 'index' => '{extends "base2"}{/extends}',
                    // 'base2' => '{extends "base"}{macro sayName($name)}Hi {$name}{/macro}{block "title" prepend}{sayName($name)} - {/block}{/extends}',
                    // 'base' => '{macro sayName($name)}Hello {$name}{/macro}<h1>{block "title"}Site{/block}</h1>',
                // ),
                // array(
                    // 'name' => 'John',
                // ),
                // '<h1>Hi John - Site</h1>',
            // ),
        );
    }

    /**
     * @dataProvider providerRender
     */
    public function testRender($output, array $resources, array $variables = array()) {
        $resourceHandler = new ArrayTemplateResourceHandler();
        $resourceHandler->setResources($resources);

        $resourceIds = array_keys($resources);

        $context = new DefaultTemplateContext($resourceHandler);

        $engine = new TemplateEngine($context);
        $result = $engine->render(reset($resourceIds), $variables);

        $this->assertEquals($output, $result);
    }

}
