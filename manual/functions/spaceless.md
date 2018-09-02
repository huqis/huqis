# Function: spaceless

Removes whitespace between HTML tags to deal with space rendering issues.

## Syntax

```spaceless($string)```

## Arguments

|Index|Name|Type|Required|Default|Description|
|---|---|---|---|---|---|
|0|string|string|yes|-|Value to remove whitespace from|

## Example

```
{$result = spaceless($string)}

{$result = $string|spaceless}
```

# See Also

- [filter](../blocks/filter.md)
- [trim](trim.md)
