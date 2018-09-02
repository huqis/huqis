# Function: raw

Omits all output filters on an output expression.

__Note: This is special syntax filter and can not actually be used as a function.__ 

## Syntax

```$string|raw```

## Arguments

|Index|Name|Type|Required|Default|Description|
|---|---|---|---|---|---|
|0|string|string|yes|-|Value to omit output filters for|

## Example

```
{autoescape true}
    {$result = $string|raw}
{/autoescape}
```

## See Also

- [autoescape](../blocks/autoescape.md)
- [escape](escape.md)
