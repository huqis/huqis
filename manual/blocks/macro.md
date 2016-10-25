# Blocks: macro 

Defines a macro (or function) inside the template syntax.

## Syntax

```
{macro $functionName([$argument1[, $argument2[, ...]]])}
    ...
{/macro}
```

## Example

```
{macro renderTitle($title)}
    <h1>{$title|lower|capitalize}</h1>
{/macro}

{macro calculateSum($variable1, $variable2)}
    {return $variable1 + $variable2}
{/macro}

{$value1 = 7}
{$value2 = 12}
{$sum = calculateSum($value1, $value2)}

{renderTitle("my title")}
{$sum}
{calculateSum($value1, $value2)}
```

will output:

```
<h1>My Title</h1>
19
19
```


## See Also

- [call](call.md)
- [return](return.md)

