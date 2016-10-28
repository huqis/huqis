# Function: default

Use a default value when the provided value is empty.

## Syntax

```default($value, $default)```

## Arguments

|Index|Name|Type|Required|Default|Description|
|---|---|---|---|---|---|
|0|value|mixed|yes|-|Value with a default fallback|
|1|default|mixed|yes|-|Default when the value is empty|

## Example

```
{$result = default($value, "Defaults to this")}

{$result = $value|default("Defaults to this")}
```
