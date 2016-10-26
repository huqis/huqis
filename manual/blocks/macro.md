# Blocks: macro 

Defines a macro (or function) inside the template syntax.

All arguments are required unless a default value is defined in the signature.
A default argument value can only be a scalar value like null, a boolean, a number or a string. 

The macro cannot access variables outside it's block.

## Syntax

```
{macro $functionName([$argument1[, $argument2 = $default[, ...]]])}
    ...
{/macro}
```

## Example

```
{macro renderTitle($title)}
    <h1>{$title|lower|capitalize}</h1>
{/macro}

{macro calculateSum($variable1, $variable2 = 12)}
    {return $variable1 + $variable2}
{/macro}

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

- [call](call.md)
- [return](return.md)

