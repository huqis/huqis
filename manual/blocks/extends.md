# Blocks: extends

Extends the current template from another template.

_Note: no output can be generated before the extends statement._

## Syntax

```
{extends $template}
    
{/extends}
```

## Example

```
{extends "parent.tpl"}

    {block name="head-title" prepnd}Page |{/block}
    
    {block name="content"}
        This is my content
    {/block}
    
{/extends}
```

## See Also

- [block](block.md)
