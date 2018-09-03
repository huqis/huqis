# Blocks: block

Defines an inheritable and overridable template block.

Blocks are used together with [```extends```](extends.md). 

You create a parent template which uses ```block``` to create placeholders.
These placeholders blocks can have a default body for when they are untouched.

The main template uses ```extends``` to include the parent template.
Inside the ```extends``` block, it defines the same blocks as the parent to override or alter those blocks.

You can ```append``` or ```prepend``` a parent block by adding this keyword to the block definition, or just replace it all together by leaving the keywords.

A block can be wrapped as well by using the ```parent``` block as a placeholder for the parent block.

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
    {block "head"}
        <title>{block "head-title"}Site{/block}</title>
    {/block}
    </head>
    <body>
    {block "body"}
        <div class="container">
        {block "content"}{/block}
        </div>
    {/block}
    {block "footer"}My footer{/block}
    </body>
</html>
```

Main template:

```html
{extends "parent.tpl"}
    {block "head-title" prepend}Page | {/block}
    {block "content"}
        This is my content
    {/block}
    {block "footer"}
        <p>Wow! {parent} is awesome!</p>
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
        <p>Wow! My footer is awesome!</p>
    </body>
</html>
```

## See Also

- [extends](extends.md)
- [parent](parent.md)
