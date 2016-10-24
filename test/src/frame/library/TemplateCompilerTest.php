<?php

namespace frame\library;

use frame\library\resource\ArrayTemplateResourceHandler;

use \PHPUnit_Framework_TestCase;

class TemplateCompilerTest extends PHPUnit_Framework_TestCase {

    public function providerCompile() {
        return array(
            array("using a bracket {without closing", " ?>using a bracket {without closing<?php "),
            array("using a bracket { with a space", " ?>using a bracket { with a space<?php "),
            array("using a bracket { with a space, and close it }", " ?>using a bracket { with a space, and close it }<?php "),
            array("using a bracket{\nand new line", " ?>using a bracket{\nand new line<?php "),
            // variables
            array('plain text', ' ?>plain text<?php '),
            array('{"test"}', 'echo "test";'),
            array('{"te\"st"}', 'echo "te\\"st";'),
            array('{ $no} space after open bracket', ' ?>{ $no} space after open bracket<?php '),
            array('open close brackets straight after each other {}', ' ?>open close brackets straight after each other {}<?php '),
            array('using a close bracket } without opening', ' ?>using a close bracket } without opening<?php '),
            array('usage of a plain {$variable}..', ' ?>usage of a plain <?php echo $context->getVariable(\'variable\'); ?>..<?php '),
            array('{$variable} to begin', 'echo $context->getVariable(\'variable\'); ?> to begin<?php '),
            array('ending with {$variable}', ' ?>ending with <?php echo $context->getVariable(\'variable\');'),
            array('{* comment *}', ''),
            array('{* {if $variable}{/if} *}', ''),
            // arrays
            array('{[$variable1, $variable2]}', '[$context->getVariable(\'variable1\'), $context->getVariable(\'variable2\')];'),
            array('{["key" = $variable1, $key = $variable2]}', '["key" => $context->getVariable(\'variable1\'), $context->getVariable(\'key\') => $context->getVariable(\'variable2\')];'),
            // variable assignment
            array('{$variable=15}', '$context->setVariable("variable", 15);'),
            array('{$variable = 15}', '$context->setVariable("variable", 15);'),
            array('{$variable = null}', '$context->setVariable("variable", null);'),
            array('{$variable = true}', '$context->setVariable("variable", true);'),
            array('{$variable = false}', '$context->setVariable("variable", false);'),
            array('{$variable = 5 < 10}', '$context->setVariable("variable", 5 < 10);'),
            array('{$variable = $otherVariable}', '$context->setVariable("variable", $context->getVariable(\'otherVariable\'));'),
            array('{$array = [$variable1, $variable2]}', '$context->setVariable("array", [$context->getVariable(\'variable1\'), $context->getVariable(\'variable2\')]);'),
            array('{$array = [$key1 = $variable1, $key2 = $variable2]}', '$context->setVariable("array", [$context->getVariable(\'key1\') => $context->getVariable(\'variable1\'), $context->getVariable(\'key2\') => $context->getVariable(\'variable2\')]);'),
            // expression
            array('{$variable + $value + 15}', 'echo $context->getVariable(\'variable\') + $context->getVariable(\'value\') + 15;'),
            array('{($variable * 3) / 5}', 'echo ($context->getVariable(\'variable\') * 3) / 5;'),
            array('{$variable = $value + 15}', '$context->setVariable("variable", $context->getVariable(\'value\') + 15);'),
            array('{$variable = 15 + $value}', '$context->setVariable("variable", 15 + $context->getVariable(\'value\'));'),
            array('{$variable = $value / (15 * 3)}', '$context->setVariable("variable", $context->getVariable(\'value\') / (15 * 3));'),
            array('{$variable.property}', 'echo $context->getVariable(\'variable.property\');'),
            array('{$variable[$property]}', 'echo $context->getVariable(\'variable\')[$context->getVariable(\'property\')];'),
            // expression with modifiers
            array('{"test"|truncate}', 'echo $context->applyModifiers("test", [[\'truncate\']]);'),
            array('{true|boolean}', 'echo $context->applyModifiers(true, [[\'boolean\']]);'),
            array('{$variable|truncate}', 'echo $context->applyModifiers($context->getVariable(\'variable\'), [[\'truncate\']]);'),
            array('{$variable|truncate:15}', 'echo $context->applyModifiers($context->getVariable(\'variable\'), [[\'truncate\', 15]]);'),
            array('{$variable|truncate:15:"..."}', 'echo $context->applyModifiers($context->getVariable(\'variable\'), [[\'truncate\', 15, "..."]]);'),
            array('{$variable|truncate:15|boolean}', 'echo $context->applyModifiers($context->getVariable(\'variable\'), [[\'truncate\', 15], [\'boolean\']]);'),
            array('{truncate($variable, 15, "...")}', 'echo $context->call(\'truncate\', [$context->getVariable(\'variable\'), 15, "..."]);'),
            array('{$variable|boolean:true}', 'echo $context->applyModifiers($context->getVariable(\'variable\'), [[\'boolean\', true]]);'),
            array('{$variable.property.subproperty|boolean:false}', 'echo $context->applyModifiers($context->getVariable(\'variable.property.subproperty\'), [[\'boolean\', false]]);'),
            array('{$variable|truncate:strlen($default)}', 'echo $context->applyModifiers($context->getVariable(\'variable\'), [[\'truncate\', $context->call(\'strlen\', [$context->getVariable(\'default\')])]]);'),
            array('{$variable = $otherVariable|truncate:15:"..."}', '$context->setVariable("variable", $context->applyModifiers($context->getVariable(\'otherVariable\'), [[\'truncate\', 15, "..."]]));'),
            // function call
            array('{time()}', 'echo $context->call(\'time\');'),
            array('{count($array)}', 'echo $context->call(\'count\', [$context->getVariable(\'array\')]);'),
            array('{count($container.value())}', 'echo $context->call(\'count\', [$context->getVariable(\'container\')->value()]);'),
            array('{function("test\\"er")}', 'echo $context->call(\'function\', ["test\\"er"]);'),
            array('{$variable = count($array)}', '$context->setVariable("variable", $context->call(\'count\', [$context->getVariable(\'array\')]));'),
            array('{$variable = substr($string, 3, 7)}', '$context->setVariable("variable", $context->call(\'substr\', [$context->getVariable(\'string\'), 3, 7]));'),
            array('{$variable = substr($string, 3, substr($string, 0, 1))}', '$context->setVariable("variable", $context->call(\'substr\', [$context->getVariable(\'string\'), 3, $context->call(\'substr\', [$context->getVariable(\'string\'), 0, 1])]));'),
            array('{$variable = substr($string, $start, $length - $start + 7)}', '$context->setVariable("variable", $context->call(\'substr\', [$context->getVariable(\'string\'), $context->getVariable(\'start\'), $context->getVariable(\'length\') - $context->getVariable(\'start\') + 7]));'),
            array('{$variable.method()}', 'echo $context->getVariable(\'variable\')->method();'),
            array('{$variable.method($argument, $argument2)}', 'echo $context->getVariable(\'variable\')->method($context->getVariable(\'argument\'), $context->getVariable(\'argument2\'));'),
            array('{$variable.method($argument + $argument2)}', 'echo $context->getVariable(\'variable\')->method($context->getVariable(\'argument\') + $context->getVariable(\'argument2\'));'),
            array('{$variable.method($argument.method())}', 'echo $context->getVariable(\'variable\')->method($context->getVariable(\'argument\')->method());'),
            array('{$variable.method($argument, "string")}', 'echo $context->getVariable(\'variable\')->method($context->getVariable(\'argument\'), "string");'),
            array('{$variable[$property].method()}', 'echo $context->getVariable(\'variable\')[$context->getVariable(\'property\')]->method();'),
            array('{$variable.method()|truncate}', 'echo $context->applyModifiers($context->getVariable(\'variable\')->method(), [[\'truncate\']]);'),
            array('{$functionName($argument)}', 'echo $context->call($context->getVariable(\'functionName\'), [$context->getVariable(\'argument\')]);'),
            array('{$variable = concat("string", $argument)|replace:"-":"_"}', '$context->setVariable("variable", $context->applyModifiers($context->call(\'concat\', ["string", $context->getVariable(\'argument\')]), [[\'replace\', "-", "_"]]));'),
            // literal block
            array('{literal}{$variable}{/literal}', ' ?>{$variable}<?php '),
            // if block
            array('{if $boolean}Yes{/if}', 'if ($context->getVariable(\'boolean\')) { $context = $context->createChild(); ?>Yes<?php $context = $context->getParent(true); }'),
            array('{if $variable == 15}Variable equals 15{/if}', 'if ($context->getVariable(\'variable\') == 15) { $context = $context->createChild(); ?>Variable equals 15<?php $context = $context->getParent(true); }'),
            array('{if $variable1&&$variable2||$variable3|modify}Ok{/if}', 'if ($context->getVariable(\'variable1\') and $context->getVariable(\'variable2\') or $context->applyModifiers($context->getVariable(\'variable3\'), [[\'modify\']])) { $context = $context->createChild(); ?>Ok<?php $context = $context->getParent(true); }'),
            array('{if $variable == 15 or $variable == 30}Variable equals 15 or 30{else}{$variable} differs from 15 and 30{/if}', 'if ($context->getVariable(\'variable\') == 15 or $context->getVariable(\'variable\') == 30) { $context = $context->createChild(); ?>Variable equals 15 or 30<?php $context = $context->getParent(true); } else { $context = $context->createChild();echo $context->getVariable(\'variable\'); ?> differs from 15 and 30<?php $context = $context->getParent(true); }'),
            array('{if $variable and ($variable == 15 or $variable == 30)}Variable equals 15 or 30{/if}', 'if ($context->getVariable(\'variable\') and ($context->getVariable(\'variable\') == 15 or $context->getVariable(\'variable\') == 30)) { $context = $context->createChild(); ?>Variable equals 15 or 30<?php $context = $context->getParent(true); }'),
            array('{if $variable|boolean == true}Variable is true{/if}', 'if ($context->applyModifiers($context->getVariable(\'variable\'), [[\'boolean\']]) == true) { $context = $context->createChild(); ?>Variable is true<?php $context = $context->getParent(true); }'),
            array('{if $variable|truncate:4:"==" == "test=="}Testing{/if}', 'if ($context->applyModifiers($context->getVariable(\'variable\'), [[\'truncate\', 4, "=="]]) == "test==") { $context = $context->createChild(); ?>Testing<?php $context = $context->getParent(true); }'),
            array('{if $variable.method($argument.method())}Testing{/if}', 'if ($context->getVariable(\'variable\')->method($context->getVariable(\'argument\')->method())) { $context = $context->createChild(); ?>Testing<?php $context = $context->getParent(true); }'),
            array('{if $variable.method($argument.method()) and true}Testing{/if}', 'if ($context->getVariable(\'variable\')->method($context->getVariable(\'argument\')->method()) and true) { $context = $context->createChild(); ?>Testing<?php $context = $context->getParent(true); }'),
            array('{if $variable.method() or (!$variable.property and $otherVariable.method())}Testing{/if}', 'if ($context->getVariable(\'variable\')->method() or (!$context->getVariable(\'variable.property\') and $context->getVariable(\'otherVariable\')->method())) { $context = $context->createChild(); ?>Testing<?php $context = $context->getParent(true); }'),
            array('{if $variable.method() or (!$variable.property and $otherVariable.method($argument)) or ($variable.property and $otherVariable.method2())}Testing{/if}', 'if ($context->getVariable(\'variable\')->method() or (!$context->getVariable(\'variable.property\') and $context->getVariable(\'otherVariable\')->method($context->getVariable(\'argument\'))) or ($context->getVariable(\'variable.property\') and $context->getVariable(\'otherVariable\')->method2())) { $context = $context->createChild(); ?>Testing<?php $context = $context->getParent(true); }'),
            array('{if isset($variable[$property])}Yes{/if}', 'if (isset($context->getVariable(\'variable\')[$context->getVariable(\'property\')])) { $context = $context->createChild(); ?>Yes<?php $context = $context->getParent(true); }'),
            array('{if $variable ~= "/regex/"}match{/if}', 'if (preg_match("/regex/", $context->getVariable(\'variable\'))) { $context = $context->createChild(); ?>match<?php $context = $context->getParent(true); }'),
            // foreach block
            array('{foreach $list as $value}loop{/foreach}', '$foreach1 = $context->getVariable(\'list\');if ($foreach1) { $foreach1Index = 0;$foreach1Length = count($foreach1);foreach ($foreach1 as $foreach1Value) { $context = $context->createChild();$context->setVariable(\'value\', $foreach1Value); ?>loop<?php $context = $context->getParent(true); }} '),
            array('{foreach $list as $value key $key loop $loop}loop {$loop.index}: {$key}{/foreach}', '$foreach1 = $context->getVariable(\'list\');if ($foreach1) { $foreach1Index = 0;$foreach1Length = count($foreach1);foreach ($foreach1 as $foreach1Key => $foreach1Value) { $context = $context->createChild();$context->setVariable(\'loop\', ["index" => $foreach1Index,"revindex" => $foreach1Length - $foreach1Index,"first" => $foreach1Index === 0,"last" => $foreach1Index === $foreach1Length - 1,"length" => $foreach1Length,]);$foreach1Index++;$context->setVariable(\'value\', $foreach1Value);$context->setVariable(\'key\', $foreach1Key); ?>loop <?php echo $context->getVariable(\'loop.index\'); ?>: <?php echo $context->getVariable(\'key\');$context = $context->getParent(true); }} '),
            // macro block
            array('{macro myFirstMacro()}loop{/macro}', '$macro1 = function(TemplateContext $context) {  ?>loop<?php  };$macro1 = new \frame\library\func\MacroTemplateFunction($macro1);$context->setFunction(\'myFirstMacro\', $macro1);'),
            array('{macro myMacro($input, $input2)}loop {$input}{/macro}', '$macro1 = function(TemplateContext $context) {  ?>loop <?php echo $context->getVariable(\'input\'); };$macro1 = new \frame\library\func\MacroTemplateFunction($macro1, [\'input\', \'input2\']);$context->setFunction(\'myMacro\', $macro1);'),
            array('{macro sum($input, $input2)}{return $input + $input2}{/macro}', '$macro1 = function(TemplateContext $context) { return $context->getVariable(\'input\') + $context->getVariable(\'input2\'); };$macro1 = new \frame\library\func\MacroTemplateFunction($macro1, [\'input\', \'input2\']);$context->setFunction(\'sum\', $macro1);'),
            // extendable block
            array('{block "name"}{myFirstMacro()}{/block}', '/*block-"name"-start*/ ?><?php echo $context->call(\'myFirstMacro\'); ?><?php /*block-"name"-end*/ ?><?php '),
            array('{block "name"}Hello {myFirstMacro()}{/block}', '/*block-"name"-start*/ ?>Hello <?php echo $context->call(\'myFirstMacro\'); ?><?php /*block-"name"-end*/ ?><?php '),
            array('{block "name"}{myFirstMacro()} there.{/block}', '/*block-"name"-start*/ ?><?php echo $context->call(\'myFirstMacro\'); ?> there.<?php /*block-"name"-end*/ ?><?php '),
        );
    }

    /**
     * @dataProvider providerCompile
     */
    public function testCompile($template, $compiled) {
        $context = new DefaultTemplateContext(new ArrayTemplateResourceHandler());
        $compiler = new TemplateCompiler($context);

        $result = $compiler->compile($template);

        $this->assertEquals($compiled, $result);
    }

    public function providerCompileFail() {
        return array(
            // missing variable or string
            array('{test}'),
            array('{$value = test}'),
            // nested not closed
            array('{$value = (15 + (7 * 3)}'),
            array('{if ($boolean}Yes{/if}'),
            array('{if $boolean)}Yes{/if}'),
            // double variable
            array('{$test$test}'),
            // double else
            array('{if $boolean}Yes{else}{else}{/if}'),
            // output before extends
            array('Test {extends "file"}{/extends}'),
            // invalid block name
            array('{block file}{/block}'),
        );
    }

    /**
     * @dataProvider providerCompileFail
     * @expectedException \frame\library\exception\CompileTemplateException
     */
    public function testCompileFail($template) {
        $context = new DefaultTemplateContext(new ArrayTemplateResourceHandler());
        $compiler = new TemplateCompiler($context);
        $compiler->compile($template);
    }

}
