# Blocks: return 

Returns a value as the result of a [```function```](function.md) block.

This block exists only inside a ```function``` block.

## Syntax

```
{return $expression}
```

## Example

```
{function calculateSum($variable1, $variable2)}
    {return $variable1 + $variable2}
{/function}

{$value1 = 7}
{$value2 = 12}
{$sum = calculateSum($value1, $value2)}

{$sum}
```

will output:

```
19
```

## See Also

- [function](function.md)
