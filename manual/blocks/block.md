# Blocks: block

Defines an inheritable and overridable template block.

Blocks are used together with [```extends```](extends.md). 

You create a parent template which uses ```block``` to create placeholders.
These placeholders blocks can have a default body for when they are untouched.

The main template uses ```extends``` to include the parent template.
Inside the ```extends``` block, it defines the same blocks as the parent to override or alter those blocks.

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
