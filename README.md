# Frame: PHP Template Engine

Frame is a PHP template engine with _&lt;insert great features talk&gt;_.

__Note: this engine is still under development, syntax may change__

## Getting Started

A template in Frame looks like this:

```html
<html>
    <head>
        <title>{block "title"}{$title|lower|capitalize}{/block}</title>
    </head>
    <body>
        <table>
        {foreach $entries as $entry key $index loop $loop}
            <tr class="entry{if $loop.first} first{elseif $loop.last} last{/if}">
                <td>{$loop.index + 1}</td>
                <td>{$entry.id}</td>
                <td>{$entry.name|truncate|escape}</td>
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

$engine = new TemplateEngine($context, $cache);

echo $engine->render('my-template.tpl', ['title' => 'My dynamic title']);
```

## Documentation

Read on about the syntax in the [template manual pages](manual/syntax.md).

To know more about the engine, read the [developer manual pages](manual/engine.md)

