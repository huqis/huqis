# Engine

## Getting Started

The engine is initialized with a template context.

A template context contains all the available functions, blocks, operators, ...
It's a passed on to the different functions and blocks as container of variables and everything it might need.

To obtain templates, the engine uses a resource handler.
Different options are available, more on this further down.

The following code sample shows how to setup your engine.

```php
<?php

use frame\library\cache\DirectoryTemplateCache;
use frame\library\resource\DirectoryTemplateResourceHandler;
use frame\library\DefaultTemplateContext;
use frame\library\TemplateEngine;

$resourceHandler = new DirectoryTemplateResourceHandler('/path/to/templates');
$context = new DefaultTemplateContext($resourceHandler);
$cache = new DirectoryTemplateCache('/path/to/cache');

$engine = new TemplateEngine($context, $cache);
```

This will initialize the engine with the default template context which looks for files on the file system.
Template caching is enabled using the file system to improve performance.

You can disable auto escaping or enable the PHP functions on your context.

```php
$context->setAllowPhpFunctions(true); 
$context->setAutoEscape(false);
``` 

If you are developing or debugging some templates, it can be useful to turn on debug mode.

```
$engine->setIsDebug(true);
```

This will use an ```include``` statement to execute the compiled templates instead of the ```eval``` function.
It's less performant but errors are easier to track.

It will also make the cache check modification times of all included resources.
This way when you edit a template, the engine will pick it up and recompile.

Now the engine is initiliazed, we can render a template.

```php
echo $engine->render('my-template.tpl', ['title' => 'My dynamic title']);
```

## Resource Handlers

A resource handler is used to implement a template backend.
Most common would be the file system but any data source could be implemented.

It's task is to fetch the template code and the modification time of a template.

Out of the box, there are 3 resource handlers.

### ArrayTemplateResourceHandler

This resource handler is used to create a template backend on the fly in the memory.

```php
<?php

use frame\library\resource\ArrayTemplateResourceHandler;

$arrayResourceHandler = new ArrayTemplateResourceHandler();
$arrayResourceHandler->setResource('hello', 'Hello {$name}!');
```

### DirectoryTemplateResourceHandler

The directory resource handler uses specified directory on the file system as template backend.
All files in a set template directory can be requested using their relative paths.

```php
<?php

use frame\library\resource\DirectoryTemplateResourceHandler;

$directoryResourceHandler = new DirectoryTemplateResourceHandler(__DIR__ . '/templates');
```

### ChainTemplateResourceHandler

Use the chain resource handler if you need multiple backends at once.

```php
<?php

use frame\library\resource\ChainTemplateResourceHandler;

$chainResourceHandler = new ChainTemplateResourceHandler();
$chainResourceHandler->setResourceHandler('array', $arrayResourceHandler);
$chainResourceHandler->setResourceHandler('directory', $directoryResourceHandler);
$chainResourceHandler->setResourceHandler('directory2', new DirectoryTemplateResourceHandler(__DIR__ . '/templates2');
$chainResourceHandler->setDefaultResourceHandler('directory');
```

With the chain, you can select the different resource handlers in your resource name by prefixing it with the name of the resource handler followed by a ```:```.

```php
$engine->render('template1.tpl');
$engine->render('array:template2');
$engine->render('directory:folder/template1.tpl');
$engine->render('directory2:template3.tpl');
```

## Cache

You can use a cache to speed up the performance of the engine.
The cache will keep all compiled templates so when a template is rendered again, the compile is already done.

The cache is very lazy by default.
If it has a cached version of the template, it will use it.

To make the cache check modification times of all included resources, so it will pick up modifications, turn on debug modus on the engine.

Out of the box there is one cache implemented.

### DirectoryTemplateCache

This cache keeps a file for each compiled template in the provided cache directory.

```php
<?php

use frame\library\cache\DirectoryTemplateCache;

$cache = new DirectoryTemplateCache(__DIR__ . '/cache');

$engine->setCache($cache);

$cache->flush();
```

## Context

The context is the scope of a running block and keeps all available variables, functions, blocks ...
It's passed on during the compile and runtime of a template to maintain scope.
Child contexts are created to perform certain logic before going back to the original context, changing the available functions, operators, ... along the way. 

### DefaultTemplateContext

This context holds the default template syntax.

```php
<?php

use frame\library\DefaultTemplateContext;

$context = new DefaultTemplateContext($chainResourceHandler);

$context->setAllowPhpFunctions(false);
$context->setAutoEscape(true);
```

You can add your own functions, operators and blocks to [extend your context](extend.md).

```php
$context->setLogicalOperator(' xor ', new GenericLogicalOperator('xor'));
$context->setExpressionOperator('~=', new RegexExpressionOperator());
$context->setFunction('truncate', new TruncateTemplateFunction());
$context->setBlock('literal', new LiteralTemplateBlock());
```

You can also use it to set global or variables for every template.

```php
$context->setVariable('engine', 'frame');
$context->setVariables(['variable1' => true, 'variable2' => 10]);
```
