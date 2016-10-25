# Function: concat

Concats the provided arguments into a string.

## Syntax

```concat($argument1 = null, $argument2 = null, ...)```

## Arguments

|Index|Name|Type|Required|Default|Description|
|---|---|---|---|---|---|
|0|argument1|mixed|no|-|Value to concatinate|
|1|argument2|mixed|no|-|Value to concatinate|
|2|...|||||

## Example

```
{$result = concat($value1, $value2, $value3)}

{$result = $value1|concat:$value2:value3}
```
