# Huqis: PHP Template Engine

Huqis is a PHP template engine with _&lt;insert great features talk&gt;_.

## Getting Started

A template in Huqis looks like this:

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

use huqis\cache\DirectoryTemplateCache;
use huqis\resource\DirectoryTemplateResourceHandler;
use huqis\DefaultTemplateContext;
use huqis\TemplateEngine;

$cache = new DirectoryTemplateCache(__DIR__ . '/cache');
$resourceHandler = new DirectoryTemplateResourceHandler(__DIR__ . '/templates');
$context = new DefaultTemplateContext($resourceHandler);

$engine = new TemplateEngine($context, $cache);

echo $engine->render('my-template.tpl', ['title' => 'My dynamic title']);
```

## Documentation

Read on about the syntax in the [template manual pages](manual/syntax.md).

To know more about the engine, read the [developer manual pages](manual/engine.md).


## Installation

You can use [Composer](http://getcomposer.org) to install this template engine.

```
composer require huqis/huqis
```
