<?php

namespace huqis;

use huqis\resource\ArrayTemplateResourceHandler;

use PHPUnit\Framework\TestCase;

class TemplateEngineTest extends TestCase {

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
            // comments
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
                '<h1>My Cool Title</h1><h2>My cool title</h2>',
                array(
                    'index' => '<h1>{$title|capitalize}</h1><h2>{$title|capitalize("first")}</h2>',
                ),
                array(
                    'title' => 'my cool title',
                ),
            ),
            array(
                '15',
                array(
                    'index' => '{$value = 15}{$value}',
                ),
            ),
            array(
                '15 15',
                array(
                    'index' => '{$array = ["key1" = 15]}{$array.key1} {$array["key1"]}',
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
            array(
                '3',
                array(
                    'index' => '{if !($value == 1)}{$value = $value + 1}{else}{$value = $value - 1}{/if}{$value}',
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
            array(
                '<ul><li>John</li><li>Jane</li><li>Mike</li></ul>',
                array(
                    'index' => '<ul>{foreach $names value $name}<li>{$name}</li>{/foreach}</ul>',
                ),
                array(
                    'names' => array('John', 'Jane', 'Mike'),
                ),
            ),
            // format tests
            array(
                '15.99 15.98765 Tue Oct 25 06:26:16 2016 2016-10-25',
                array(
                    'index' => '{$value = 15.987654321}{$value|format("number")} {$value|format("number",5)} {$timestamp = 1477376776}{$timestamp|format("date")} {$timestamp|format("date", "%F")}',
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
            // function tests
            array(
                '<p>Hello, my name is John</p><p>Hello, my name is Jane</p>',
                array(
                    'index' => '{function sayName($name)}<p>Hello, my name is {$name}</p>{/function}{foreach $names as $name}{sayName($name)}{/foreach}',
                ),
                array(
                    'names' => array('John', 'Jane'),
                ),
            ),
            array(
                'Hello, my name is Joe',
                array(
                    'index' => '{function sayName($name)}{$name}{/function}Hello, my name is {$echoName($name)}',
                ),
                array(
                    'echoName' => 'sayName',
                    'name' => 'Joe',
                ),
            ),
            array(
                '<p>Hello, my name is John</p><p>Hello, my name is Jane</p>',
                array(
                    'index' => '{function sayName($name)}<p>Hello, my name is {$name}</p>{/function}{include "include"}',
                    'include' => '{foreach $names as $name}{sayName($name)}{/foreach}',
                ),
                array(
                    'names' => array('John', 'Jane'),
                ),
            ),
            array(
                '<p>Hello, my name is John</p><p>Hello, my name is Jane</p>',
                array(
                    'index' => '{function sayName($name)}<p>Hello, my name is {$name}</p>{/function}{include $include}',
                    'include' => '{foreach $names as $name}{sayName($name)}{/foreach}',
                ),
                array(
                    'names' => array('John', 'Jane'),
                    'include' => 'include',
                ),
            ),
            array(
                '19',
                array(
                    'index' => '{function calculateSum($variable1, $variable2)}{$value3 = "scope test"}{return $variable1 + $variable2}{/function}{$value1 = 7}{$value2 = 12}{$sum = calculateSum($value1, $value2)}{$sum}{$value3}',
                ),
            ),
            array(
                'test3',
                array(
                    'index' => '{function renderSomething($variable1 = 1, $variable2 = 2, $variable3 = "test", $variable4 = null)}{return $variable3 ~ ($variable1 + $variable2)}{/function}{renderSomething()}',
                ),
            ),
            // macro tests
            array(
                '<p>Hello, my name is John and I\'m 30 years old.</p>',
                array(
                    'index' => '{function sayName($name, $age)}<p>Hello, my name is {$name} and I\'m {$age} years old.</p>{/function}{$age = 30}{macro sayName($macro, $age)}{$name}{/macro}',
                ),
                array(
                    'name' => 'John',
                ),
            ),
            array(
                '<p>Hello, my name is John and I\'m 30 years old.</p>',
                array(
                    'index' => '{function sayName($name, $age)}<p>Hello, my name is {$name} and I\'m {$age} years old.</p>{/function}{macro sayName($macro, 30)}{$name}{/macro}',
                ),
                array(
                    'name' => 'John',
                ),
            ),
            array(
                '&lt;test&gt;',
                array(
                    'index' => '{$variable = "<test>"}{autoescape}{$variable}{/autoescape}',
                ),
            ),
            // capture tests
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
            array(
                'My Title',
                array(
                    'index' => '{include "helper" with $values}',
                    'helper' => '{$value}',
                ),
                array(
                    'values' => array(
                        'value' => 'My Title',
                    ),
                ),
            ),
            array(
                'My Title',
                array(
                    'index' => '{include $template with $values}',
                    'helper' => '{$value}',
                ),
                array(
                    'template' => 'helper',
                    'values' => array(
                        'value' => 'My Title',
                    ),
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
                'index <h1>Section - Site</h1>test and <div>Title</div>',
                array(
                    'index' => 'index {extends "base"}{block "title" prepend}{$title} - {/block}{/extends} and {extends "base2"}{block "title"}{$subtitle}{/block}{/extends}',
                    'base2' => '{block "container"}<div>{block "title"}{/block}</div>{/block}',
                    'base' => '<h1>{block "title"}Site{/block}</h1>{block "container"}test{/block}',
                ),
                array(
                    'title' => 'Section',
                    'subtitle' => 'Title',
                ),
            ),
            array(
                '<div>Section</div>test',
                array(
                    'index' => '{extends "base"}{block "container" prepend}{extends "block"}{block "title"}{$title}{/block}{/extends}{/block}{/extends}',
                    'block' => '{block "block-container"}<div>{block "title"}{/block}</div>{/block}',
                    'base' => '{block "container"}test{/block}',
                ),
                array(
                    'title' => 'Section',
                    'subtitle' => 'Title',
                ),
            ),
            array(
                'Head<h2>Heading 2: Section</h2>Foot',
                array(
                    'index' => 'Head{$base = "template-2"}{extends $base}{block "title" append}: {$title}{/block}{/extends}Foot',
                    'template-1' => '<h1>{block "title"}Heading 1{/block}</h1>',
                    'template-2' => '<h2>{block "title"}Heading 2{/block}</h2>',
                ),
                array(
                    'title' => 'Section',
                ),
            ),
            array(
                'Hello My Name Is John',
                array(
                    'index' => '{filter capitalize|escape}hello my name is {$name}{/filter}',
                ),
                array(
                    'name' => 'john',
                ),
            ),

            // Nope, functions/functions should be defined before a block is first used
            // array(
                // array(
                    // 'index' => '{extends "base2"}{/extends}',
                    // 'base2' => '{extends "base"}{function sayName($name)}Hi {$name}{/function}{block "title" prepend}{sayName($name)} - {/block}{/extends}',
                    // 'base' => '{function sayName($name)}Hello {$name}{/function}<h1>{block "title"}Site{/block}</h1>',
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
        $context->setAutoEscape(false);

        $engine = new TemplateEngine($context);
        $result = $engine->render(reset($resourceIds), $variables);

        $this->assertEquals($output, $result);
    }

    /**
     * @dataProvider providerRenderWithOutputFilter
     */
    public function testRenderWithOutputFilter($output, array $resources, array $variables = array()) {
        $resourceHandler = new ArrayTemplateResourceHandler();
        $resourceHandler->setResources($resources);

        $resourceIds = array_keys($resources);

        $context = new DefaultTemplateContext($resourceHandler);
        $context->setAutoEscape(true);

        $engine = new TemplateEngine($context);
        $result = $engine->render(reset($resourceIds), $variables);

        $this->assertEquals($output, $result);
    }

    public function providerRenderWithOutputFilter() {
        return array(
            array(
                '&lt;test&gt;',
                array(
                    'index' => '{$variable = "<test>"}{$variable}',
                ),
            ),
            array(
                '&lt;test&gt;',
                array(
                    'index' => '{$variable = "<test>"}{$variable|escape}',
                ),
            ),
            array(
                '<test>',
                array(
                    'index' => '{$variable = "<test>"}{$variable|raw}',
                ),
            ),
            array(
                '<test>',
                array(
                    'index' => '{autoescape false}{$variable = "<test>"}{$variable}{/autoescape}',
                ),
            ),
            array(
                '<h1>hello <john!</h1>',
                array(
                    'index' => '{filter capitalize|raw}<h1>hello {$name}!</h1>{/filter}',
                ),
                array(
                    'name' => '<john',
                ),
            ),
        );
    }

    public function providerRenderThrowsException() {
        return array(
            array(
                array(
                    'index' => '{include "unexistant"}',
                ),
            ),
            array(
                array(
                    'index' => '{include "helper" with }',
                    'helper' => '',
                ),
            ),
            array(
                array(
                    'index' => '{include "helper" with "only-arrays-allowed"}',
                    'helper' => '',
                ),
            ),
            array(
                array(
                    'index' => '{extends "base"}{block "unexistant"}{/block}{/extends}',
                    'base' => '{block "title"}{/block}',
                ),
            ),
        );
    }

    /**
     * @dataProvider providerRenderThrowsException
     * @expectedException \huqis\exception\TemplateException
     */
    public function testRenderThrowsException(array $resources) {
        $resourceHandler = new ArrayTemplateResourceHandler();
        $resourceHandler->setResources($resources);

        $resourceIds = array_keys($resources);

        $context = new DefaultTemplateContext($resourceHandler);
        $context->setAutoEscape(false);

        $engine = new TemplateEngine($context);
        $engine->render(reset($resourceIds), []);
    }

}
