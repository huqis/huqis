# Function: trim

Strips whitespace characters or whatever you provide, equivalent of PHP's [trim](http://php.net/manual/en/function.trim.php).

## Syntax

```trim($string[, $characters])```

## Arguments

|Index|Name|Type|Required|Default|Description|
|---|---|---|---|---|---|
|0|string|string|yes|-|String to trim|
|1|characters|string|no| whitespace characters |Characters to trim|

## Example

```
{$result = trim($string)}
{$result = trim($string, "-")}

{$result = $string|trim}
{$result = $string|trim:"-"}
```
