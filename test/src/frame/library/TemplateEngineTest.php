<?php

namespace frame\library;

use frame\library\resource\ArrayTemplateResourceHandler;

use \PHPUnit_Framework_TestCase;

class TemplateEngineTest extends PHPUnit_Framework_TestCase {

    public function providerRender() {
        return array(
            // 0
            array(
                array(
                    'index' => 'Hello, my name is {$name}',
                ),
                array(
                    'name' => 'Joe'
                ),
                'Hello, my name is Joe',
            ),
            // if test
            // 1
            array(
                array(
                    'index' => '{if $value == 1}{$value = $value + 1}{else}{$value = $value - 1}{/if}{$value}',
                ),
                array(
                    'value' => 1,
                ),
                '2'
            ),
            // 2
            array(
                array(
                    'index' => '{if $value == 1}{$value = $value + 1}{else}{$value = $value - 1}{/if}{$value}',
                ),
                array(
                    'value' => 2,
                ),
                '1'
            ),
            // foreach test
            // 3
            array(
                array(
                    'index' => '<ul>{foreach $names as $name loop $loop}<li>{$loop.index + 1} / {$loop.length}: {$name} {if $loop.first}[F]{elseif $loop.last}[L]{/if}</li>{/foreach}</ul>',
                ),
                array(
                    'names' => array('John', 'Jane', 'Mike'),
                ),
                '<ul><li>1 / 3: John [F]</li><li>2 / 3: Jane </li><li>3 / 3: Mike [L]</li></ul>'
            ),
            // macro tests
            array(
                array(
                    'index' => '{macro sayName($name)}{$name}{/macro}Hello, my name is {$echoName($name)}',
                ),
                array(
                    'echoName' => 'sayName',
                    'name' => 'Joe',
                ),
                'Hello, my name is Joe',
            ),
            array(
                array(
                    'index' => '{macro sayName($name)}<p>Hello, my name is {$name}</p>{/macro}{foreach $names as $name}{sayName($name)}{/foreach}',
                ),
                array(
                    'names' => array('John', 'Jane'),
                ),
                '<p>Hello, my name is John</p><p>Hello, my name is Jane</p>',
            ),
            array(
                array(
                    'index' => '{macro sayName($name)}<p>Hello, my name is {$name}</p>{/macro}{include "include"}',
                    'include' => '{foreach $names as $name}{sayName($name)}{/foreach}',
                ),
                array(
                    'names' => array('John', 'Jane'),
                ),
                '<p>Hello, my name is John</p><p>Hello, my name is Jane</p>',
            ),
            array(
                array(
                    'index' => '{macro sayName($name)}<p>Hello, my name is {$name}</p>{/macro}{include $include}',
                    'include' => '{foreach $names as $name}{sayName($name)}{/foreach}',
                ),
                array(
                    'names' => array('John', 'Jane'),
                    'include' => 'include',
                ),
                '<p>Hello, my name is John</p><p>Hello, my name is Jane</p>',
            ),
            array(
                array(
                    'index' => '{macro sayName($name, $test)}<p>Hello, my name is {$name} {$test}</p>{/macro}{sayName()}',
                ),
                array(),
                '<p>Hello, my name is  </p>',
            ),
            // call tests
            array(
                array(
                    'index' => '{macro sayName($name, $age)}<p>Hello, my name is {$name} and I\'m {$age} years old.</p>{/macro}{$age = 30}{call sayName($_call, $age)}{$name}{/call}',
                ),
                array(
                    'name' => 'John',
                ),
                '<p>Hello, my name is John and I\'m 30 years old.</p>',
            ),
            array(
                array(
                    'index' => '{macro sayName($name, $age)}<p>Hello, my name is {$name} and I\'m {$age} years old.</p>{/macro}{call sayName($_call, 30)}{$name}{/call}',
                ),
                array(
                    'name' => 'John',
                ),
                '<p>Hello, my name is John and I\'m 30 years old.</p>',
            ),
            // assign tests
            array(
                array(
                    'index' => '{$age = 30}{assign $hello}{$age = $age + 5}<p>Hello, my name is {$name} and in 5 years, I\'m {$age} years old.</p>{/assign}{$hello} {$age}',
                ),
                array(
                    'name' => 'John',
                ),
                '<p>Hello, my name is John and in 5 years, I\'m 35 years old.</p> 30',
            ),
            // extends tests
            array(
                array(
                    'index' => '{extends "base"}{block "title"}{$title}{/block}{/extends}',
                    'base' => '<h1>{block "title"}Default Title{/block}</h1>',
                ),
                array(
                    'title' => 'My Title',
                ),
                '<h1>My Title</h1>',
            ),
            array(
                array(
                    'index' => '{extends "base"}{block "title" append} - {$title}{/block}{/extends}',
                    'base' => '<h1>{block "title"}Default Title{/block}</h1>',
                ),
                array(
                    'title' => 'My Title',
                ),
                '<h1>Default Title - My Title</h1>',
            ),
            array(
                array(
                    'index' => '{extends "base"}{block "title" prepend}{$title} - {/block}{/extends}',
                    'base' => '<h1>{block "title"}Default Title{/block}</h1>',
                ),
                array(
                    'title' => 'My Title',
                ),
                '<h1>My Title - Default Title</h1>',
            ),
            array(
                array(
                    'index' => '{extends "base2"}{block "title" prepend}{$subtitle} - {/block}{/extends}',
                    'base2' => '{extends "base"}{block "title" prepend}{$title} - {/block}{/extends}',
                    'base' => '<h1>{block "title"}Site{/block}</h1>',
                ),
                array(
                    'title' => 'Section',
                    'subtitle' => 'Title',
                ),
                '<h1>Title - Section - Site</h1>',
            ),
            array(
                array(
                    'index' => '{$base = "template-2"}{extends $base}{block "title" append}: {$title}{/block}{/extends}',
                    'template-1' => '<h1>{block "title"}Heading 1{/block}</h1>',
                    'template-2' => '<h2>{block "title"}Heading 2{/block}</h2>',
                ),
                array(
                    'title' => 'Section',
                ),
                '<h2>Heading 2: Section</h2>',
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
    public function testRender(array $resources, array $variables, $output) {
        $resourceHandler = new ArrayTemplateResourceHandler();
        $resourceHandler->setResources($resources);

        $resourceIds = array_keys($resources);

        $context = new DefaultTemplateContext($resourceHandler);

        $engine = new TemplateEngine($context);
        $result = $engine->render(reset($resourceIds), $variables);

        $this->assertEquals($output, $result);
    }

}
