# Blocks: block

Defines an inheritable and overridable template block.

## Syntax

```
{block $name [append|prepend]}
    
{/block}
```

## Example

Parent template:

```
<html>
    <head>
    {block name="head"}
        <title>{block name="head-title"}Site{/block}</title>
    {/block}
    </head>
    <body>
    {block name="body"}
        <div class="container">
        {block name="content"}{/block}
        </div>
    {/block}
    </body>
</html>
```

Main template:

```
{extends "parent.tpl"}
    {block name="head-title" prepend}Page | {/block}
    {block name="content"}
        This is my content
    {/block}
{/extends}
```

will output:

```
<html>
    <head>
        <title>Page | Site</title>
    </head>
    <body>
        <div class="container">
            This is my content
        </div>
    </body>
</html>
```

## See Also

- [extends](extends.md)
