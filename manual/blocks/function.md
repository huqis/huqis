# Blocks: function 

Defines a function inside the template syntax.

All arguments are required unless a default value is defined in the signature.
A default argument value can only be a scalar value like null, a boolean, a number or a string. 

The function cannot access variables outside it's block.

Use the [```return```](return.md) block if you want the function to return something when called.

## Syntax

```
{function $functionName([$argument1[, $argument2 = $default[, ...]]])}
    ...
{/function}
```

## Example

```
{function renderTitle($title)}
    <h1>{$title|lower|capitalize}</h1>
{/function}

{function calculateSum($variable1, $variable2 = 12)}
    {return $variable1 + $variable2}
{/function}

{$value1 = 7}
{$value2 = 3}
{$sum = calculateSum($value1)}

{renderTitle("my title")}
{$sum}
{calculateSum($value1, $value2)}
```

will output:

```
<h1>My Title</h1>
19
10
```


## See Also

- [macro](macro.md)
- [return](return.md)

