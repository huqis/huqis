# Frame: PHP Template Engine

Frame is a PHP template engine with _&lt;insert great features talk&gt;_.

Check a basic example of the template syntax:

```html
<html>
    <head>
        <title>{$title}</title>
    </head>
    <body>
        <table>
        {foreach $entries as $entry}
            <tr>
                <td>{$entry.id}</td>
                <td>{$entry.name|escape}</td>
            </tr>
        {/foreach}
        </table>
    </body>
</html>
```

The following code sample should get your template engine up and runnig in PHP:

```php
<?php

use frame\library\cache\DirectoryTemplateCache;
use frame\library\resource\DirectoryTemplateResourceHandler;
use frame\library\DefaultTemplateContext;
use frame\library\TemplateEngine;

$cache = new DirectoryTemplateCache(__DIR__ . '/cache');
$resourceHandler = new DirectoryTemplateResourceHandler(__DIR__ . '/templates');
$context = new DefaultTemplateContext($resourceHandler);

$engine = new TemplateEngine($context);
$engine->setCache($directoryCache);

echo $engine->render('my-template.tpl', ['title' => 'My dynamic title']);
```

## What's In This Library

### TemplateBlock

The ```TemplateBlock``` interface is used to implement custom template blocks in PHP.

Implementations out of the box:
- ```AssignTemplateBlock```
- ```BlockTemplateBlock```
- ```BreakTemplateBlock```
- ```CallTemplateBlock```
- ```ContinueTemplateBlock```
- ```CycleTemplateBlock```
- ```ElseIfTemplateBlock```
- ```ElseTemplateBlock```
- ```ExtendsTemplateBlock```
- ```ForeachTemplateBlock```
- ```IfTemplateBlock```
- ```IncludeTemplateBlock```
- ```LiteralTemplateBlock```
- ```MacroTemplateBlock```
- ```ReturnTemplateBlock```

### TemplateFunction

The ```TemplateFunction``` interface is used to implement custom template functions in PHP.
Template functions can also be used as a modifier.

Implementations out of the box:
- ```CapitalizeTemplateFunction```
- ```ConcatTemplateFunction```
- ```DefaultTemplateFunction```
- ```EscapeTemplateFunction```
- ```LowerTemplateFunction```
- ```ReplaceTemplateFunction```
- ```TruncateTemplateFunction```
- ```UpperTemplateFunction```

### ExpressionOperator

The ```ExpressionOperator``` interface is used to implement template expression operators like +, -, <, ==, ...

Implementations out of the box:
- ```AssignExpressionOperator```
- ```GenericExpressionOperator```
- ```RegexExpressionOperator```

### LogicalOperator

The ```LogicalOperator``` interface is used to implement template logical operators like and, &&, or, ...

Implementations out of the box:
- ```GenericLogicalOperator```

### TemplateResourceHandler

The ```TemplateResourceHandler``` interface is used to implement a template backend.
It's task is to fetch the template code and the modification time of a template.

#### ArrayTemplateResourceHandler

The ```ArrayTemplateResourceHandler``` is used to create a template backend on the fly in the memory.

```php
<?php

use frame\library\resource\ArrayTemplateResourceHandler;

$arrayResourceHandler = new ArrayTemplateResourceHandler();
$arrayResourceHandler->setResource('hello', 'Hello {$name}!');
```

#### DirectoryTemplateResourceHandler

The ```DirectoryTemplateResourceHandler``` uses a directory as template backend.
All files in a set template directory can be requested using their relative paths.

```php
<?php

use frame\library\resource\DirectoryTemplateResourceHandler;

$directoryResourceHandler = new DirectoryTemplateResourceHandler(__DIR__ . '/templates');
```

#### ChainTemplateResourceHandler

The ```ChainTemplateResourceHandler``` is used to implement multiple template backends at once.

```php
<?php

use frame\library\resource\ChainTemplateResourceHandler;

$chainResourceHandler = new ChainTemplateResourceHandler();
$chainResourceHandler->setResourceHandler('array', $arrayResourceHandler);
$chainResourceHandler->setResourceHandler('directory', $directoryResourceHandler);
$chainResourceHandler->setDefaultResourceHandler('directory');
```

### TemplateContext

The ```TemplateContext``` class holds the context during the compile and runtime of a template.
The context is the scope of a running block and keeps all available variables, functions, blocks ...

You can create child contexts and easily go back to the parent, changing the available functions, operators, ... along the way. 

#### DefaultTemplateContext

The ```DefaultTemplateContext``` initializes the default template syntax.

```php
<?php

use frame\library\DefaultTemplateContext;

$context = new DefaultTemplateContext($chainResourceHandler);
```

### TemplateCompiler

The ```TemplateCompiler``` class compiles the template syntax into native PHP code.

### TemplateOutputBuffer

The ```TemplateOutputBuffer``` class is used as output buffer for the compile process.

Template blocks should generate output on this buffer.

### TemplateEngine

The ```TemplateEngine``` class is the main facade to the template engine.
When a template needs to be rendered, you request it on this class.

```php
<?php

use frame\library\TemplateEngine;

$engine = new TemplateEngine($context);
$output .= $engine->render('test/hello.tpl', ['name' => 'John']);
```
### TemplateCache

The ```TemplateCache``` interface is used to cache the compiled templates.

#### DirectoryTemplateCache

The ```DirectoryTemplateCache``` class keeps a file for each compiled template in the provided cache directory.

```php
<?php

use frame\library\cache\DirectoryTemplateCache;

$directoryCache = new DirectoryTemplateCache(__DIR__ . '/cache');

$engine->setCache($directoryCache);
```

## Code Sample

Check the following code sample to see it all glued together:

```php
<?php

use frame\library\block\LiteralTemplateBlock;
use frame\library\cache\DirectoryTemplateCache;
use frame\library\func\TruncateTemplateFunction;
use frame\library\operator\expression\RegexExpressionOperator;
use frame\library\operator\logical\GenericLogicalOperator;
use frame\library\resource\ArrayTemplateResourceHandler;
use frame\library\resource\ChainTemplateResourceHandler;
use frame\library\resource\DirectoryTemplateResourceHandler;
use frame\library\resource\TemplateResourceHandler;
use frame\library\DefaultTemplateContext;
use frame\library\TemplateEngine;

function createTemplateEngine() {
    // first we need a resource handler to get our templates from

    // a resource handler with the resources set in memory
    $arrayResourceHandler = new ArrayTemplateResourceHandler();
    $arrayResourceHandler->setResource('hello', 'Hello {$name}!');

    // a resource handler with the resources from a directory
    $directoryResourceHandler = new DirectoryTemplateResourceHandler(__DIR__ . '/templates');

    // a resource handler which combines other resource handlers
    $chainResourceHandler = new ChainTemplateResourceHandler();
    $chainResourceHandler->setResourceHandler('array', $arrayResourceHandler);
    $chainResourceHandler->setResourceHandler('directory', $directoryResourceHandler);
    $chainResourceHandler->setDefaultResourceHandler('directory');

    // our resource handler is ready.
    // let's create a default context with it with the template syntax ready to use
    $context = new DefaultTemplateContext($chainResourceHandler);

    // you can add or override functions, operators, ...
    // the following operators and functions are part of the default context
    $context->setLogicalOperator(' xor ', new GenericLogicalOperator('xor'));
    $context->setExpressionOperator('~=', new RegexExpressionOperator());
    $context->setFunction('truncate', new TruncateTemplateFunction());
    $context->setBlock('literal', new LiteralTemplateBlock());

    // or set initial variables
    $context->setVariable('engine', 'frame');

    // allow PHP functions
    $context->setAllowPhpFunctions(true);

    // our context is initialized, create the engine
    $engine = new TemplateEngine($context);
    
    // use a cache to store compiled templates
    $directoryCache = new DirectoryTemplateCache(__DIR__ . '/cache');

    $engine->setCache($directoryCache);

    return $engine;
}

function renderTemplates(TemplateEngine $engine) {
    $output = '';
    // render template from the directory resource handler
    $output .= $engine->render('test/hello.tpl', ['name' => 'John']);
    $output .= $engine->render('directory:test/hello.tpl', ['name' => 'John']);

    // render a template from the array resource handler
    $output .= $engine->render('array:hello', ['name' => 'John']);

    return $output;
}

echo renderTemplates(createTemplateEngine());
```
