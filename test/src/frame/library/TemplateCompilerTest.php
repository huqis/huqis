<?php

namespace frame\library;

use frame\library\resource\ArrayTemplateResourceHandler;

use \PHPUnit_Framework_TestCase;

class TemplateCompilerTest extends PHPUnit_Framework_TestCase {

    public function providerCompile() {
        return array(
            // syntax
            array("using a bracket {without closing", 'echo "using a bracket {without closing";'),
            array("using a bracket { with a space", 'echo "using a bracket { with a space";'),
            array("using a bracket { with a space, and close it }", 'echo "using a bracket ";echo "{";echo " with a space, and close it ";echo "}";'),
            array("using a bracket{\nand new line", "echo \"using a bracket{\nand new line\";"),
            array('using a close bracket } without opening', 'echo "using a close bracket } without opening";'),
            array('open close brackets straight after each other {}', 'echo "open close brackets straight after each other ";echo "{";echo "}";'),
            array('{ $no} space after open bracket', 'echo "{";echo " \\$no";echo "}";echo " space after open bracket";'),
            array('{* comment *}', ''),
            array('{* {if $variable}{/if} *}', ''),
            // variables
            array('plain text', 'echo "plain text";'),
            array('{"test"}', 'echo "test";'),
            array('{"te\"st"}', 'echo "te\\"st";'),
            array('{15.987}', 'echo 15.987;'),
            array('usage of a plain {$variable} in the middle', 'echo "usage of a plain ";echo $context->getVariable(\'variable\', false);echo " in the middle";'),
            array('{$variable} to begin', 'echo $context->getVariable(\'variable\', false);echo " to begin";'),
            array('ending with {$variable}', 'echo "ending with ";echo $context->getVariable(\'variable\', false);'),
            // arrays
            array('{[$variable1, $variable2]}', '[$context->getVariable(\'variable1\', false), $context->getVariable(\'variable2\', false)];'),
            array('{["key" = $variable1, $key = $variable2]}', '["key" => $context->getVariable(\'variable1\', false), $context->getVariable(\'key\', false) => $context->getVariable(\'variable2\', false)];'),
            // variable assignment
            array('{$variable=15}', '$context->setVariable("variable", 15);'),
            array('{$variable = 15}', '$context->setVariable("variable", 15);'),
            array('{$variable = null}', '$context->setVariable("variable", null);'),
            array('{$variable = true}', '$context->setVariable("variable", true);'),
            array('{$variable = false}', '$context->setVariable("variable", false);'),
            array('{$variable = 5 < 10}', '$context->setVariable("variable", 5 < 10);'),
            array('{$variable = $otherVariable}', '$context->setVariable("variable", $context->getVariable(\'otherVariable\', false));'),
            array('{$array = [$variable1, $variable2]}', '$context->setVariable("array", [$context->getVariable(\'variable1\', false), $context->getVariable(\'variable2\', false)]);'),
            array('{$array = [$key1 = $variable1, $key2 = $variable2]}', '$context->setVariable("array", [$context->getVariable(\'key1\', false) => $context->getVariable(\'variable1\', false), $context->getVariable(\'key2\', false) => $context->getVariable(\'variable2\', false)]);'),
            array('{$variable = functionCall()}', '$context->setVariable("variable", $context->call(\'functionCall\'));'),
            // expression
            array('{$variable + $value + 15}', 'echo $context->getVariable(\'variable\', false) + $context->getVariable(\'value\', false) + 15;'),
            array('{($variable * 3) / 5}', 'echo ($context->getVariable(\'variable\', false) * 3) / 5;'),
            array('{$variable = $value + 15}', '$context->setVariable("variable", $context->getVariable(\'value\', false) + 15);'),
            array('{$variable = 15 + $value}', '$context->setVariable("variable", 15 + $context->getVariable(\'value\', false));'),
            array('{$variable = $value / (15 * 3)}', '$context->setVariable("variable", $context->getVariable(\'value\', false) / (15 * 3));'),
            array('{$variable.property}', 'echo $context->getVariable(\'variable.property\');'),
            array('{$variable[$property]}', 'echo $context->getVariable(\'variable\', false)[$context->getVariable(\'property\', false)];'),
            // expression with modifiers
            array('{"test"|truncate}', 'echo $context->applyModifiers("test", [[\'truncate\']]);'),
            array('{true|boolean}', 'echo $context->applyModifiers(true, [[\'boolean\']]);'),
            array('{$variable|truncate}', 'echo $context->applyModifiers($context->getVariable(\'variable\', false), [[\'truncate\']]);'),
            array('{$variable|truncate:15}', 'echo $context->applyModifiers($context->getVariable(\'variable\', false), [[\'truncate\', 15]]);'),
            array('{$variable|truncate:15:"..."}', 'echo $context->applyModifiers($context->getVariable(\'variable\', false), [[\'truncate\', 15, "..."]]);'),
            array('{$variable|truncate:15|boolean}', 'echo $context->applyModifiers($context->getVariable(\'variable\', false), [[\'truncate\', 15], [\'boolean\']]);'),
            array('{truncate($variable, 15, "...")}', 'echo $context->call(\'truncate\', [$context->getVariable(\'variable\', false), 15, "..."]);'),
            array('{$variable|boolean:true}', 'echo $context->applyModifiers($context->getVariable(\'variable\', false), [[\'boolean\', true]]);'),
            array('{$variable.property.subproperty|boolean:false}', 'echo $context->applyModifiers($context->getVariable(\'variable.property.subproperty\'), [[\'boolean\', false]]);'),
            array('{$variable|truncate:strlen($default)}', 'echo $context->applyModifiers($context->getVariable(\'variable\', false), [[\'truncate\', $context->call(\'strlen\', [$context->getVariable(\'default\', false)])]]);'),
            array('{$variable = $otherVariable|truncate:15:"..."}', '$context->setVariable("variable", $context->applyModifiers($context->getVariable(\'otherVariable\', false), [[\'truncate\', 15, "..."]]));'),
            // function call
            array('{time()}', 'echo $context->call(\'time\');'),
            array('{count($array)}', 'echo $context->call(\'count\', [$context->getVariable(\'array\', false)]);'),
            array('{count($container.value())}', 'echo $context->call(\'count\', [$context->getVariable(\'container\', false)->value()]);'),
            array('{function("test\\"er")}', 'echo $context->call(\'function\', ["test\\"er"]);'),
            array('{$variable = count($array)}', '$context->setVariable("variable", $context->call(\'count\', [$context->getVariable(\'array\', false)]));'),
            array('{$variable = substr($string, 3, 7)}', '$context->setVariable("variable", $context->call(\'substr\', [$context->getVariable(\'string\', false), 3, 7]));'),
            array('{$variable = substr($string, 3, substr($string, 0, 1))}', '$context->setVariable("variable", $context->call(\'substr\', [$context->getVariable(\'string\', false), 3, $context->call(\'substr\', [$context->getVariable(\'string\', false), 0, 1])]));'),
            array('{$variable = substr($string, $start, $length - $start + 7)}', '$context->setVariable("variable", $context->call(\'substr\', [$context->getVariable(\'string\', false), $context->getVariable(\'start\', false), $context->getVariable(\'length\', false) - $context->getVariable(\'start\', false) + 7]));'),
            array('{$variable.method()}', 'echo $context->getVariable(\'variable\', false)->method();'),
            array('{$variable.method($argument, $argument2)}', 'echo $context->getVariable(\'variable\', false)->method($context->getVariable(\'argument\', false), $context->getVariable(\'argument2\', false));'),
            array('{$variable.method($argument + $argument2)}', 'echo $context->getVariable(\'variable\', false)->method($context->getVariable(\'argument\', false) + $context->getVariable(\'argument2\', false));'),
            array('{$variable.method($argument.method())}', 'echo $context->getVariable(\'variable\', false)->method($context->getVariable(\'argument\', false)->method());'),
            array('{$variable.method($argument, "string")}', 'echo $context->getVariable(\'variable\', false)->method($context->getVariable(\'argument\', false), "string");'),
            array('{$variable[$property].method()}', 'echo $context->getVariable(\'variable\', false)[$context->getVariable(\'property\', false)]->method();'),
            array('{$variable.method()|truncate}', 'echo $context->applyModifiers($context->getVariable(\'variable\', false)->method(), [[\'truncate\']]);'),
            array('{$functionName($argument)}', 'echo $context->call($context->getVariable(\'functionName\', false), [$context->getVariable(\'argument\', false)]);'),
            array('{$variable = concat("string", $argument)|replace:"-":"_"}', '$context->setVariable("variable", $context->applyModifiers($context->call(\'concat\', ["string", $context->getVariable(\'argument\', false)]), [[\'replace\', "-", "_"]]));'),
            // literal block
            array('{literal}{$variable}{/literal}', 'echo "{\\$variable}";'),
            // if block
            array('{if $boolean}Yes{/if}', 'if ($context->getVariable(\'boolean\', false)) {echo "Yes";}'),
            array('{if $variable == 15}Variable equals 15{/if}', 'if ($context->getVariable(\'variable\', false) == 15) {echo "Variable equals 15";}'),
            array('{if $variable1&&$variable2||$variable3|modify}Ok{/if}', 'if ($context->getVariable(\'variable1\', false) and $context->getVariable(\'variable2\', false) or $context->applyModifiers($context->getVariable(\'variable3\', false), [[\'modify\']])) {echo "Ok";}'),
            array('{if $variable == 15 or $variable == 30}Variable equals 15 or 30{else}{$variable} differs from 15 and 30{/if}', 'if ($context->getVariable(\'variable\', false) == 15 or $context->getVariable(\'variable\', false) == 30) {echo "Variable equals 15 or 30";} else {echo $context->getVariable(\'variable\', false);echo " differs from 15 and 30";}'),
            array('{if $variable and ($variable == 15 or $variable == 30)}Variable equals 15 or 30{/if}', 'if ($context->getVariable(\'variable\', false) and ($context->getVariable(\'variable\', false) == 15 or $context->getVariable(\'variable\', false) == 30)) {echo "Variable equals 15 or 30";}'),
            array('{if $variable|boolean == true}Variable is true{/if}', 'if ($context->applyModifiers($context->getVariable(\'variable\', false), [[\'boolean\']]) == true) {echo "Variable is true";}'),
            array('{if $variable|truncate:4:"==" == "test=="}Testing{/if}', 'if ($context->applyModifiers($context->getVariable(\'variable\', false), [[\'truncate\', 4, "=="]]) == "test==") {echo "Testing";}'),
            array('{if $variable.method($argument.method())}Testing{/if}', 'if ($context->getVariable(\'variable\', false)->method($context->getVariable(\'argument\', false)->method())) {echo "Testing";}'),
            array('{if $variable.method($argument.method()) and true}Testing{/if}', 'if ($context->getVariable(\'variable\', false)->method($context->getVariable(\'argument\', false)->method()) and true) {echo "Testing";}'),
            array('{if $variable.method() or (!$variable.property and $otherVariable.method())}Testing{/if}', 'if ($context->getVariable(\'variable\', false)->method() or (!$context->getVariable(\'variable.property\') and $context->getVariable(\'otherVariable\', false)->method())) {echo "Testing";}'),
            array('{if $variable.method() or (!$variable.property and $otherVariable.method($argument)) or ($variable.property and $otherVariable.method2())}Testing{/if}', 'if ($context->getVariable(\'variable\', false)->method() or (!$context->getVariable(\'variable.property\') and $context->getVariable(\'otherVariable\', false)->method($context->getVariable(\'argument\', false))) or ($context->getVariable(\'variable.property\') and $context->getVariable(\'otherVariable\', false)->method2())) {echo "Testing";}'),
            array('{if isset($variable[$property])}Yes{/if}', 'if (isset($context->getVariable(\'variable\', false)[$context->getVariable(\'property\', false)])) {echo "Yes";}'),
            array('{if $variable ~= "/regex/"}match{/if}', 'if (preg_match("/regex/", $context->getVariable(\'variable\', false))) {echo "match";}'),
            // foreach block
            array('{foreach $list as $value}loop{/foreach}', '$foreach1 = $context->getVariable(\'list\', false);if ($foreach1) { foreach ($foreach1 as $foreach1Value) {$context->setVariable(\'value\', $foreach1Value);echo "loop";}}unset($foreach1);'),
            array('{foreach $list as $value key $key loop $loop}loop {$loop.index}: {$key}{/foreach}', '$foreach1 = $context->getVariable(\'list\', false);if ($foreach1) { $foreach1Index = 0;$foreach1Length = count($foreach1);foreach ($foreach1 as $foreach1Key => $foreach1Value) {$context->setVariable(\'loop\', ["index" => $foreach1Index,"revindex" => $foreach1Length - $foreach1Index,"first" => $foreach1Index === 0,"last" => $foreach1Index === $foreach1Length - 1,"length" => $foreach1Length,]);$foreach1Index++;$context->setVariable(\'value\', $foreach1Value);$context->setVariable(\'key\', $foreach1Key);echo "loop ";echo $context->getVariable(\'loop.index\');echo ": ";echo $context->getVariable(\'key\', false);}$context->setVariable(\'loop\', null);}unset($foreach1);'),
            // function block
            array('{function myFirstMacro()}loop{/function}', '$function1 = function(TemplateContext $context) { echo "loop"; };$context->setFunction(\'myFirstMacro\', new \frame\library\func\FunctionTemplateFunction("myFirstMacro", $function1));'),
            array('{function myMacro($input, $input2)}loop {$input}{/function}', '$function1 = function(TemplateContext $context) { echo "loop ";echo $context->getVariable(\'input\', false); };$context->setFunction(\'myMacro\', new \frame\library\func\FunctionTemplateFunction("myMacro", $function1, [\'input\', \'input2\'], []));'),
            array('{function sum($input, $input2 = 2)}{return $input + $input2}{/function}', '$function1 = function(TemplateContext $context) { return $context->getVariable(\'input\', false) + $context->getVariable(\'input2\', false); };$context->setFunction(\'sum\', new \frame\library\func\FunctionTemplateFunction("sum", $function1, [\'input\', \'input2\'], [1 => 2]));'),
            // extendable block
            array('{block "name"}{myFirstMacro()}{/block}', 'echo $context->call(\'myFirstMacro\');'),
            array('{block "name"}Hello {myFirstMacro()}{/block}', 'echo "Hello ";echo $context->call(\'myFirstMacro\');'),
            array('{block "name"}{myFirstMacro()} there.{/block}', 'echo $context->call(\'myFirstMacro\');echo " there.";'),
            /*
            */
        );
    }

    /**
     * @dataProvider providerCompile
     */
    public function testCompile($template, $compiled) {
        $context = new DefaultTemplateContext(new ArrayTemplateResourceHandler());
        $context->preCompile();

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
            array('{if true}{block "file"}{else}{/block}{/if}'),
        );
    }

    /**
     * @dataProvider providerCompileFail
     * @expectedException \frame\library\exception\CompileTemplateException
     */
    public function testCompileFail($template) {
        $context = new DefaultTemplateContext(new ArrayTemplateResourceHandler());
        $context->preCompile();

        $compiler = new TemplateCompiler($context);

        $compiler->compile($template);
    }


    /**
     * @dataProvider providerCompileExpression
     */
    public function testCompileExpression($expression, $compiled) {
        $context = new DefaultTemplateContext(new ArrayTemplateResourceHandler());
        $context->preCompile();

        $compiler = new TemplateCompiler($context);
        $compiler->compile('');

        $result = $compiler->compileExpression($expression);

        $this->assertEquals($compiled, $result);
    }

    public function providerCompileExpression() {
        return array(
            // syntax
            array('$list', '$context->getVariable(\'list\', false)'),
        );
    }

}
