# Function: format

Formats the provided string or number.

## Syntax

```format($value, $format[, $extra])```

## Arguments

|Index|Name|Type|Required|Default|Description|
|---|---|---|---|---|---|
|0|value|mixed|yes|-|Value to format|
|1|format|string|yes|-|Format to apply|
|2|extra|mixed|no|-|Extra argument for the format|

### Formats

- __date__: Formats a date into the current locale, equivalent of PHP's [date](http://php.net/manual/en/function.date.php).
The ```extra``` argument is the date format.
- __number__: Formats a number, equivalent of PHP's [number_format](http://php.net/manual/en/function.number_format.php).
The ```extra``` argument is the number of decimals.
- __json__: Encodes the value as json, equivalent of PHP's [json_encode](http://php.net/manual/en/function.json_encode.php).
No ```extra``` argument allowed for this format.

## Example

```
{$value = 15.987654321}
{$value|format("number")}
{$value|format("number", 5)}

{$timestamp = 1477376776}
{$timestamp|format("date")}
{$timestamp|format("date", "H:i")}
```

will output:

```
15.99
15.98765

Tue, 25 Oct 2016 00:45:10 +0000
00:45
```
