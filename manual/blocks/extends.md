# Blocks: extends

Includes another template in the current context and alters the template's [```block```](block.md) placeholders.

You cannot create new ```block``` placeholders inside this block, they should exist in the extended template.

Output is not allowed except when inside a ```block``` or [```function```](function.md) block.

You can nest this block, meaning an ```extends``` inside an ```extends```,  but you cannot use a ```block``` name which is used in one of the parent ```extends``` blocks.

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
- [function](function.md)
