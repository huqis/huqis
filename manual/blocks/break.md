# Blocks: break

Breaks the current ```foreach``` block.

## Syntax

```
{break}
```

## Example

```
{$values = [1, 2, 3, 4, 5, 6, 7, 8, 9]}
{foreach $values as $value}
    {if $value == 3}
        {break}
    {/if}
    {$value}<br />
{/foreach}
```

will output:

```
1
2
```

## See Also

- [continue](continue.md)
- [foreach](foreach.md)
