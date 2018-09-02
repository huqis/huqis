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

use huqis\cache\DirectoryTemplateCache;
use huqis\resource\DirectoryTemplateResourceHandler;
use huqis\DefaultTemplateContext;
use huqis\TemplateEngine;

$resourceHandler = new DirectoryTemplateResourceHandler('/path/to/templates');
$context = new DefaultTemplateContext($resourceHandler);
$cache = new DirectoryTemplateCache('/path/to/cache');

$engine = new TemplateEngine($context, $cache);
```

This will initialize the engine with the default template context which looks for files on the file system.
Template caching is enabled using the file system to improve performance.

You can disable auto escaping or enable the PHP functions on your context.

```php
$context->setAutoEscape(false); // on by default
$context->setAllowPhpFunctions(true); // off by default
``` 

### Debug Mode

If you are developing or debugging some templates, it is useful to turn on debug mode.

```
$engine->setIsDebug(true);
```

This will use an ```include``` statement to execute the compiled templates instead of the ```eval``` function.
It's less performant but errors are easier to track.

It will also make the cache check modification times of all included resources.
This way when you edit a template, the engine will pick it up and recompile.

Now the engine is initialized, we can render a template.

```php
echo $engine->render('my-template.tpl', [
    'title' => 'My dynamic title',
]);
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

use huqis\resource\ArrayTemplateResourceHandler;

$arrayResourceHandler = new ArrayTemplateResourceHandler();
$arrayResourceHandler->setResource('hello', 'Hello {$name}!');
```

### DirectoryTemplateResourceHandler

The directory resource handler uses specified directory on the file system as template backend.
All files in a set template directory can be requested using their relative paths.

```php
<?php

use huqis\resource\DirectoryTemplateResourceHandler;

$directoryResourceHandler = new DirectoryTemplateResourceHandler(__DIR__ . '/templates');
```

### ChainTemplateResourceHandler

Use the chain resource handler if you need multiple backends at once.

```php
<?php

use huqis\resource\ChainTemplateResourceHandler;

$chainResourceHandler = new ChainTemplateResourceHandler();
$chainResourceHandler->setResourceHandler('array', $arrayResourceHandler);
$chainResourceHandler->setResourceHandler('directory', $directoryResourceHandler);
$chainResourceHandler->setResourceHandler('directory2', new DirectoryTemplateResourceHandler(__DIR__ . '/templates2');
$chainResourceHandler->setDefaultResourceHandler('directory');
```

Chained resource handlers will be polled in the order they are registered.
You can specify the resource handler in your resource name by prefixing it with the name of the resource handler followed by a colon ```:```.

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

Clear the cache by calling the ```flush``` method.

```php
$cache->flush();
```

The cache can be enabled or disabled at runtime by setting it to the engine.

```php
$engine->setCache($cache);
$engine->setCache(null);
```

Out of the box there 2 cache implementations.

### ArrayTemplateCache

This cache keeps a compiled template in memory to use again in the same request.
The cache is never persisted so it's starts empty for every request.

```php
<?php

use huqis\cache\ArrayTemplateCache;

$cache = new ArrayTemplateCache();
```

### DirectoryTemplateCache

This cache keeps a file for each compiled template in the provided cache directory.

```php
<?php

use huqis\cache\DirectoryTemplateCache;

$cache = new DirectoryTemplateCache(__DIR__ . '/cache');
```

## Context

The context is the scope of a running block and keeps all available variables, functions, blocks ...
It's passed on during the compile and runtime of a template to maintain scope.
Child contexts are created to perform certain logic before going back to the original context, changing the available functions, operators, ... along the way.

You can use a generic ```TemplateContext``` for an empty context or the ```DefaultTemplateContext``` which holds the default template syntax as documented in these manual pages.

```php
<?php

use huqis\DefaultTemplateContext;

$context = new DefaultTemplateContext($chainResourceHandler);

$context->setAllowPhpFunctions(false);
$context->setAutoEscape(true);
```

### Output Filters

By default, auto escaping is enabled. 
This is actually a shortcut to the output filters.

An output filter is a defined template function where your output is passed through.

Add as many output filters to the context as you want.

```
$context->setOutputFilter("auto-escape", "escape");
$context->setOutputFilter("my-filter", "truncate", [200, ""]);
``` 

You can use [autoescape](blocks/autoescape.md) and [raw](functions/raw.md) to influence the output filters while inside a template.

### Extend Your Context

You can add your own functions, operators and blocks to [extend your context](extend.md).

```php
$context->setLogicalOperator(' xor ', new GenericLogicalOperator('xor'));
$context->setExpressionOperator('~=', new RegexExpressionOperator());
$context->setFunction('truncate', new TruncateTemplateFunction());
$context->setBlock('literal', new LiteralTemplateBlock());
```

Use the initial context to set global or variables for every template.

```php
$context->setVariable('engine', 'huqis');
$context->setVariables([
    'variable1' => true, 
    'variable2' => 10,
]);
```
