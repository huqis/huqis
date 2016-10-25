# Blocks: continue

Forwards to the next iteration in a ```foreach``` block and skips the rest of the block code.

## Syntax

```
{continue}
```

## Example

```
{$values = [1, 2, 3, 4, 5, 6, 7, 8, 9]}
{foreach $values as $value}
    {if $value == 3}
        {continue}
    {/if}
    {$value}<br />
{/foreach}
```

will output:

```
1
2
4
5
6
7
8
9
```

## See Also

- [break](break.md)
- [foreach](foreach.md)



