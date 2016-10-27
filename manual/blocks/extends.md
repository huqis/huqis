# Blocks: extends

Extends a block from another template and alters the template's blocks.

Inside an ```extends```, you cannot generate output except from inside a ```block``` or ```macro```.

You can create nested extends blocks but you cannot use a block name of a parent block.

## Syntax

```
{extends $template}
    
{/extends}
```

## Example

```
{extends "head.tpl"}
    {block name="title" prepend}Page | {/block}
{/extends}

{block name="body"}{/block}

{extends "footer.tpl"}
    {block name="body"}
        This is my footer
    {/block}
{/extends}
```

## See Also

- [block](block.md)
- [include](include.md)
- [macro](macro.md)
