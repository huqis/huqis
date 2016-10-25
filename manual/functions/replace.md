# Function: replace

Simple search and replace function, equivalent of PHP's [str_replace](http://php.net/manual/en/function.str_replace.php).

## Syntax

```replace($string, $search, $replace)```

## Arguments

|Index|Name|Type|Required|Default|Description|
|---|---|---|---|---|---|
|0|string|string|yes|-|String to perform a search and replace on|
|1|search|string|yes|-|Value to search|
|2|replace|string|yes|-|Value to replace the matches with|

## Example

```
{$result = replace($string, "search", "replace")}

{$result = $string|replace:"search":"replace"}
```
