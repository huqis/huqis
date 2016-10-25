# Function: capitalize

Capitalizes the provided string.

## Syntax

```capitalize($string)```

## Arguments

|Index|Name|Type|Required|Default|Description|
|---|---|---|---|---|---|
|0|string|string|yes|-|String to capitalize|
|1|type|string|no|html|Type of capitalization|

### Types

- __all__: Capitalizes all first characters, equivalent of PHP's [ucwords](http://php.net/manual/en/function.ucwords.php)
- __first__: Capitalizes the first character of the string, equivalent of PHP's [ucfirst](http://php.net/manual/en/function.ucfirst.php)

## Example

```
{$result = capitalize($string)}

{$result = $string|capitalize}
```
