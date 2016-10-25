# Blocks: if 

Renders template syntax depending on a condition.

## Syntax

```
{if $expression}
   ...
{elseif $expression}
   ...
{else}
   ...
{/if}
```

## Example

```
{$value1 = 7}
{if $value < 7}
    less
{elseif $value == 7}
    equals
{else}
    greater
{/if}
```

will output:

```
equals
```
