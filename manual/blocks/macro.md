# Blocks: macro

Calls a function with the block contents as the ```$macro``` argument.

## Syntax

```
{macro $function([$argument1, [$argument2, [ ... ]]])}
    
{/macro}
```

## Example

```
{macro truncate($macro, 10)}
    The contents of the $macro argument
{/macro}
```

will output:

```
The con...
```


## See Also

- [function](function.md)
