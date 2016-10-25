# Blocks: return 

Returns a value from inside a ```macro``` block.

## Syntax

```
{return $variable}
```

## Example

```
{macro calculateSum($variable1, $variable2)}
    {return $variable1 + $variable2}
{/macro}

{$value1 = 7}
{$value2 = 12}
{$sum = calculateSum($value1, $value2)}

{$sum}
```

will output:

```
19
```
