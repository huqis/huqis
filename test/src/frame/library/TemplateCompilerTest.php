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
            array("using a bracket { with a space, and close it }", 'echo "using a bracket { with a space, and close it }";'),
            array("using a bracket{\nand new line", "echo \"using a bracket{\\nand new line\";"),
            array('using a close bracket } without opening', 'echo "using a close bracket } without opening";'),
            array('open close brackets straight after each other {}', 'echo "open close brackets straight after each other {}";'),
            array('{ $no} space after open bracket', 'echo "{ \\$no} space after open bracket";'),
            array('{* comment *}', ''),
            array('{* {if $variable}{/if} *}', ''),
            // variables
            array('plain text', 'echo "plain text";'),
            array('{"test"}', 'echo "test";'),
            array('{"te\"st"}', 'echo "te\\"st";'),
            array('{15.987}', 'echo 15.987;'),
            array('usage of a plain {$variable} in the middle', 'echo "usage of a plain ";' . "\n" . 'echo $context->getVariable(\'variable\', false);' . "\n" . 'echo " in the middle";'),
            array('{$variable} to begin', 'echo $context->getVariable(\'variable\', false);' . "\n" . 'echo " to begin";'),
            array('ending with {$variable}', 'echo "ending with ";' . "\n" . 'echo $context->getVariable(\'variable\', false);'),
            // arrays
            array('{[$variable1, $variable2]}', '[$context->getVariable(\'variable1\', false), $context->getVariable(\'variable2\', false)];'),
            array('{["key" = $variable1, $key = $variable2]}', '["key" => $context->getVariable(\'variable1\', false), $context->getVariable(\'key\', false) => $context->getVariable(\'variable2\', false)];'),
            // variable assignment
            array('{$variable=15}', '$context->setVariable("variable", 15, false);'),
            array('{$variable = 15}', '$context->setVariable("variable", 15, false);'),
            array('{$variable = null}', '$context->setVariable("variable", null, false);'),
            array('{$variable = true}', '$context->setVariable("variable", true, false);'),
            array('{$variable = false}', '$context->setVariable("variable", false, false);'),
            array('{$variable = []}', '$context->setVariable("variable", [], false);'),
            array('{$variable = "test"}', '$context->setVariable("variable", "test", false);'),
            array('{$variable = "te\\"st"}', '$context->setVariable("variable", "te\\"st", false);'),
            array('{$variable = "\\}-\\{ardle"}', '$context->setVariable("variable", "}-{ardle", false);'),
            array('{$variable = 5 < 10}', '$context->setVariable("variable", 5 < 10, false);'),
            array('{$variable = $otherVariable}', '$context->setVariable("variable", $context->getVariable(\'otherVariable\', false), false);'),
            array('{$array = [$variable1, $variable2]}', '$context->setVariable("array", [$context->getVariable(\'variable1\', false), $context->getVariable(\'variable2\', false)], false);'),
            array('{$array = [$key1 = $variable1, $key2 = $variable2]}', '$context->setVariable("array", [$context->getVariable(\'key1\', false) => $context->getVariable(\'variable1\', false), $context->getVariable(\'key2\', false) => $context->getVariable(\'variable2\', false)], false);'),
            array('{$variable = functionCall()}', '$context->setVariable("variable", $context->call(\'functionCall\'), false);'),
            array('{$attributes["data-order"] = "true"}', '{ $assign = $context->getVariable(\'attributes\'); $assign["data-order"] = "true"; $context->setVariable(\'attributes\', $assign); unset($assign); };'),
            // expression
            array('{$variable + $value + 15}', 'echo $context->getVariable(\'variable\', false) + $context->getVariable(\'value\', false) + 15;'),
            array('{($variable * 3) / 5}', 'echo ($context->getVariable(\'variable\', false) * 3) / 5;'),
            array('{$variable = $value + 15}', '$context->setVariable("variable", $context->getVariable(\'value\', false) + 15, false);'),
            array('{$variable = 15 + $value}', '$context->setVariable("variable", 15 + $context->getVariable(\'value\', false), false);'),
            array('{$variable = $value / (15 * 3)}', '$context->setVariable("variable", $context->getVariable(\'value\', false) / (15 * 3), false);'),
            array('{$variable.property}', 'echo $context->getVariable(\'variable.property\');'),
            array('{$variable[$property]}', 'echo $context->getVariable(\'variable\', false)[$context->getVariable(\'property\', false)];'),
            // expression with modifiers
            array('{"test"|truncate}', 'echo $context->applyModifiers("test", [[\'truncate\']]);'),
            array('{true|boolean}', 'echo $context->applyModifiers(true, [[\'boolean\']]);'),
            array('{$variable|truncate}', 'echo $context->applyModifiers($context->getVariable(\'variable\', false), [[\'truncate\']]);'),
            array('{$variable|truncate(15)}', 'echo $context->applyModifiers($context->getVariable(\'variable\', false), [[\'truncate\', 15]]);'),
            array('{$variable|truncate(15, "...")}', 'echo $context->applyModifiers($context->getVariable(\'variable\', false), [[\'truncate\', 15, "..."]]);'),
            array('{$variable|truncate(15)|boolean}', 'echo $context->applyModifiers($context->getVariable(\'variable\', false), [[\'truncate\', 15], [\'boolean\']]);'),
            array('{truncate($variable, 15, "...")}', 'echo $context->call(\'truncate\', [$context->getVariable(\'variable\', false), 15, "..."]);'),
            array('{$variable|boolean(true)}', 'echo $context->applyModifiers($context->getVariable(\'variable\', false), [[\'boolean\', true]]);'),
            array('{$variable.property.subproperty|boolean(false)}', 'echo $context->applyModifiers($context->getVariable(\'variable.property.subproperty\'), [[\'boolean\', false]]);'),
            array('{$variable|truncate(strlen($default))}', 'echo $context->applyModifiers($context->getVariable(\'variable\', false), [[\'truncate\', $context->call(\'strlen\', [$context->getVariable(\'default\', false)])]]);'),
            array('{$variable = $otherVariable|truncate(15, "...")}', '$context->setVariable("variable", $context->applyModifiers($context->getVariable(\'otherVariable\', false), [[\'truncate\', 15, "..."]]), false);'),
            array('{$row.name|replace("[", "")|replace("]", "")}', 'echo $context->applyModifiers($context->getVariable(\'row.name\'), [[\'replace\', "[", ""], [\'replace\', "]", ""]]);'),
            // function call
            array('{time()}', 'echo $context->call(\'time\');'),
            array('{count($array)}', 'echo $context->call(\'count\', [$context->getVariable(\'array\', false)]);'),
            array('{count($container.value())}', 'echo $context->call(\'count\', [$context->ensureObject($context->getVariable(\'container\', false), \'Could not call value(): $container is not an object\')->value()]);'),
            array('{function("test\\"er")}', 'echo $context->call(\'function\', ["test\\"er"]);'),
            array('{$variable = count($array)}', '$context->setVariable("variable", $context->call(\'count\', [$context->getVariable(\'array\', false)]), false);'),
            array('{$variable = substr($string, 3, 7)}', '$context->setVariable("variable", $context->call(\'substr\', [$context->getVariable(\'string\', false), 3, 7]), false);'),
            array('{$variable = substr($string, 3, substr($string, 0, 1))}', '$context->setVariable("variable", $context->call(\'substr\', [$context->getVariable(\'string\', false), 3, $context->call(\'substr\', [$context->getVariable(\'string\', false), 0, 1])]), false);'),
            array('{$variable = substr($string, $start, $length - $start + 7)}', '$context->setVariable("variable", $context->call(\'substr\', [$context->getVariable(\'string\', false), $context->getVariable(\'start\', false), $context->getVariable(\'length\', false) - $context->getVariable(\'start\', false) + 7]), false);'),
            array('{$variable.method()}', 'echo $context->ensureObject($context->getVariable(\'variable\', false), \'Could not call method(): $variable is not an object\')->method();'),
            array('{$variable.method($argument, $argument2)}', 'echo $context->ensureObject($context->getVariable(\'variable\', false), \'Could not call method($argument, $argument2): $variable is not an object\')->method($context->getVariable(\'argument\', false), $context->getVariable(\'argument2\', false));'),
            array('{$variable.method($argument + $argument2)}', 'echo $context->ensureObject($context->getVariable(\'variable\', false), \'Could not call method($argument + $argument2): $variable is not an object\')->method($context->getVariable(\'argument\', false) + $context->getVariable(\'argument2\', false));'),
            array('{$variable.method($argument.method())}', 'echo $context->ensureObject($context->getVariable(\'variable\', false), \'Could not call method($argument.method()): $variable is not an object\')->method($context->ensureObject($context->getVariable(\'argument\', false), \'Could not call method(): $argument is not an object\')->method());'),
            array('{$variable.method($argument, "string")}', 'echo $context->ensureObject($context->getVariable(\'variable\', false), \'Could not call method($argument, "string"): $variable is not an object\')->method($context->getVariable(\'argument\', false), "string");'),
            array('{$variable[$property].method()}', 'echo $context->ensureObject($context->getVariable(\'variable\', false)[$context->getVariable(\'property\', false)], \'Could not call method(): $variable[$property] is not an object\')->method();'),
            array('{$variable.method()|truncate}', 'echo $context->applyModifiers($context->ensureObject($context->getVariable(\'variable\', false), \'Could not call method()|truncate: $variable is not an object\')->method(), [[\'truncate\']]);'),
            array('{$functionName($argument)}', 'echo $context->call($context->getVariable(\'functionName\', false), [$context->getVariable(\'argument\', false)]);'),
            array('{$variable = concat("string", $argument)|replace("-", "_")}', '$context->setVariable("variable", $context->applyModifiers($context->call(\'concat\', ["string", $context->getVariable(\'argument\', false)]), [[\'replace\', "-", "_"]]), false);'),
            array('{translate("label.date.example", ["example" = time()|date_format($row.format), "format" = $row.format])}', 'echo $context->call(\'translate\', ["label.date.example", ["example" => $context->applyModifiers($context->call(\'time\'), [[\'date_format\', $context->getVariable(\'row.format\')]]), "format" => $context->getVariable(\'row.format\')]]);'),
            // literal block
            array('{literal}{$variable}{/literal}', 'echo "{\\$variable}";'),
            // if block
            array('{if $boolean}Yes{/if}', 'if ($context->getVariable(\'boolean\', false)) {' . "\n" . '    echo "Yes";' . "\n" . '}'),
            array('{if $variable == 15}Variable equals 15{/if}', 'if ($context->getVariable(\'variable\', false) == 15) {' . "\n" . '    echo "Variable equals 15";' . "\n" . '}'),
            array('{if $variable1&&$variable2||$variable3|modify}Ok{/if}', 'if ($context->getVariable(\'variable1\', false) and $context->getVariable(\'variable2\', false) or $context->applyModifiers($context->getVariable(\'variable3\', false), [[\'modify\']])) {' . "\n" . '    echo "Ok";' . "\n" . '}'),
            array('{if $variable == 15 or $variable == 30}Variable equals 15 or 30{else}{$variable} differs from 15 and 30{/if}', 'if ($context->getVariable(\'variable\', false) == 15 or $context->getVariable(\'variable\', false) == 30) {' . "\n" . '    echo "Variable equals 15 or 30";' . "\n" . '} else {' . "\n" . '    echo $context->getVariable(\'variable\', false);' . "\n" . '    echo " differs from 15 and 30";' . "\n" . '}'),
            array('{if $variable and ($variable == 15 or $variable == 30)}Variable equals 15 or 30{/if}', 'if ($context->getVariable(\'variable\', false) and ($context->getVariable(\'variable\', false) == 15 or $context->getVariable(\'variable\', false) == 30)) {' . "\n" . '    echo "Variable equals 15 or 30";' . "\n" . '}'),
            array('{if $variable|boolean == true}Variable is true{/if}', 'if ($context->applyModifiers($context->getVariable(\'variable\', false), [[\'boolean\']]) == true) {' . "\n" . '    echo "Variable is true";' . "\n" . '}'),
            array('{if $variable|truncate(4, "==") == "test=="}Testing{/if}', 'if ($context->applyModifiers($context->getVariable(\'variable\', false), [[\'truncate\', 4, "=="]]) == "test==") {' . "\n" . '    echo "Testing";' . "\n" . '}'),
            array('{if $variable.method($argument.method())}Testing{/if}', 'if ($context->ensureObject($context->getVariable(\'variable\', false), \'Could not call method($argument.method()): $variable is not an object\')->method($context->ensureObject($context->getVariable(\'argument\', false), \'Could not call method(): $argument is not an object\')->method())) {' . "\n" . '    echo "Testing";' . "\n" . '}'),
            array('{if $variable.method($argument.method()) and true}Testing{/if}', 'if ($context->ensureObject($context->getVariable(\'variable\', false), \'Could not call method($argument.method()): $variable is not an object\')->method($context->ensureObject($context->getVariable(\'argument\', false), \'Could not call method(): $argument is not an object\')->method()) and true) {' . "\n" . '    echo "Testing";' . "\n" . '}'),
            array('{if $variable.method() or (!$variable.property and $otherVariable.method())}Testing{/if}', 'if ($context->ensureObject($context->getVariable(\'variable\', false), \'Could not call method(): $variable is not an object\')->method() or (!$context->getVariable(\'variable.property\') and $context->ensureObject($context->getVariable(\'otherVariable\', false), \'Could not call method(): $otherVariable is not an object\')->method())) {' . "\n" . '    echo "Testing";' . "\n" . '}'),
            array('{if $variable.method() or (!$variable.property and $otherVariable.method($argument)) or ($variable.property and $otherVariable.method2())}Testing{/if}', 'if ($context->ensureObject($context->getVariable(\'variable\', false), \'Could not call method(): $variable is not an object\')->method() or (!$context->getVariable(\'variable.property\') and $context->ensureObject($context->getVariable(\'otherVariable\', false), \'Could not call method($argument): $otherVariable is not an object\')->method($context->getVariable(\'argument\', false))) or ($context->getVariable(\'variable.property\') and $context->ensureObject($context->getVariable(\'otherVariable\', false), \'Could not call method2(): $otherVariable is not an object\')->method2())) {' . "\n" . '    echo "Testing";' . "\n" . '}'),
            array('{if isset($variable[$property])}Yes{/if}', 'if (isset($context->getVariable(\'variable\', false)[$context->getVariable(\'property\', false)])) {' . "\n" . '    echo "Yes";' . "\n" . '}'),
            array('{if $variable ~= "/regex/"}match{/if}', 'if (preg_match("/regex/", $context->getVariable(\'variable\', false))) {' . "\n" . '    echo "match";' . "\n" . '}'),
            array('{if true}{elseif $type == "component" && $row.getOption("embed")}{/if}', 'if (true) {' . "\n" . '} elseif ($context->getVariable(\'type\', false) == "component" and $context->ensureObject($context->getVariable(\'row\', false), \'Could not call getOption("embed"): $row is not an object\')->getOption("embed")) {' . "\n" . '}'),
            // foreach block
            array('{foreach $list as $value}loop{/foreach}',
'$foreach1 = $context->getVariable(\'list\', false);
if ($foreach1) {
    foreach ($foreach1 as $foreach1Value) {
        $context->setVariable(\'value\', $foreach1Value, false);
        echo "loop";
    }
}
unset($foreach1);'
            ),
            array('{foreach $list as $value key $key loop $loop}loop {$loop.index}: {$key}{/foreach}',
'$foreach1 = $context->getVariable(\'list\', false);
if ($foreach1) {
    $foreach1Index = 0;
    $foreach1Length = count($foreach1);
    foreach ($foreach1 as $foreach1Key => $foreach1Value) {
        $context->setVariable(\'loop\', [
            "index" => $foreach1Index,
            "revindex" => $foreach1Length - $foreach1Index,
            "first" => $foreach1Index === 0,
            "last" => $foreach1Index === $foreach1Length - 1,
            "length" => $foreach1Length,
        ], false);
        $foreach1Index++;
        $context->setVariable(\'value\', $foreach1Value, false);
        $context->setVariable(\'key\', $foreach1Key, false);
        echo "loop ";
        echo $context->getVariable(\'loop.index\');
        echo ": ";
        echo $context->getVariable(\'key\', false);
    }
    $context->setVariable(\'loop\', null, false);
}
unset($foreach1);'
            ),
            // function block
            array('{function myFirstMacro()}loop{/function}', '$function1 = function(TemplateContext $context) {' . "\n" . '    echo "loop";' . "\n" . '};' . "\n" . '$context->setFunction(\'myFirstMacro\', new \frame\library\func\FunctionTemplateFunction("myFirstMacro", $function1));'),
            array('{function myMacro($input, $input2)}loop {$input}{/function}', '$function1 = function(TemplateContext $context) {' . "\n" . '    echo "loop ";' . "\n" . '    echo $context->getVariable(\'input\', false);' . "\n" . '};' . "\n" . '$context->setFunction(\'myMacro\', new \frame\library\func\FunctionTemplateFunction("myMacro", $function1, [\'input\', \'input2\'], []));'),
            array('{function sum($input, $input2 = 2)}{return $input + $input2}{/function}', '$function1 = function(TemplateContext $context) {' . "\n" . '    return $context->getVariable(\'input\', false) + $context->getVariable(\'input2\', false);' . "\n" . '};' . "\n" . '$context->setFunction(\'sum\', new \frame\library\func\FunctionTemplateFunction("sum", $function1, [\'input\', \'input2\'], [1 => 2]));'),
            // extendable block
            array('{block "name"}{/block}', '/*block-"name"-start*/' . "\n" . '/*block-"name"-end*/'),
            array('{block "name"}Hello{/block}', '/*block-"name"-start*/' . "\n" . 'echo "Hello";' . "\n" . '/*block-"name"-end*/'),
            array('{block "name"}Hello {myFirstMacro()}{/block}', '/*block-"name"-start*/' . "\n" . 'echo "Hello ";' . "\n" . 'echo $context->call(\'myFirstMacro\');' . "\n" . '/*block-"name"-end*/'),
            array('{block "name"}{myFirstMacro()} there.{/block}', '/*block-"name"-start*/' . "\n" . 'echo $context->call(\'myFirstMacro\');' . "\n" . 'echo " there.";' . "\n" . '/*block-"name"-end*/'),
            /*
            */
        );
    }

    /**
     * @dataProvider providerCompile
     */
    public function testCompile($template, $compiled) {
        $context = new DefaultTemplateContext(new ArrayTemplateResourceHandler());
        $context->setAutoEscape(false);
        $context->preCompile();

        $compiler = new TemplateCompiler($context);

        $result = $compiler->compile($template);

        $this->assertEquals($compiled . ($compiled != '' ? "\n" : ''), $result);
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
            array('{foreach $values key $key}{if true}{/foreach}{/if}'),
        );
    }

    /**
     * @dataProvider providerCompileFail
     * @expectedException \frame\library\exception\CompileTemplateException
     */
    public function testCompileFail($template) {
        $context = new DefaultTemplateContext(new ArrayTemplateResourceHandler());
        $context->setAutoEscape(false);
        $context->preCompile();

        $compiler = new TemplateCompiler($context);

        $compiler->compile($template);
    }


    /**
     * @dataProvider providerCompileExpression
     */
    public function testCompileExpression($expression, $compiled) {
        $context = new DefaultTemplateContext(new ArrayTemplateResourceHandler());
        $context->setAutoEscape(false);
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
