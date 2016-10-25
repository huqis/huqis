# Function: truncate

This function truncates a value to a specified length.

## Syntax

```truncate($string, $length = 80, $etc = '...', $breakWords = false)```

## Arguments

|Index|Name|Type|Required|Default|Description|
|---|---|---|---|---|---|
|0|string|string|yes|-|String to truncate|
|1|length|integer|no|80|Maximum length of the string|
|2|etc|string|no|...|String to replace the trunacated text with. The length of this string is included in the truncate length|
|2|breakWords|boolean|no|false|Set to true if you don't care about word boundary|

## Example

```
{$result = truncate($string)}
{$result = truncate($string, 80, "...", false)}

{$result = $string|truncate}
{$result = $string|truncate:50:"..."}
```
