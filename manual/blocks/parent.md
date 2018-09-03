# Blocks: parent

Sets a placeholder for the parent block when overriding a template block.

Parent can only be used inside a [```block```](block.md). 

## Syntax

```
{parent}
```

## Example

Parent template:

```
<html>
    <body>
    {block "footer"}
        My footer
    {/block}
    </body>
</html>
```

Main template:

```html
{extends "parent.tpl"}
    {block "footer"}
        <p>Wow! {parent} is awesome!</p>
    {/block}
{/extends}
```

will output:

```
<html>
    <body>
        <p>Wow! My footer is awesome!</p>
    </body>
</html>
```

## See Also

- [block](block.md)
- [extends](extends.md)
