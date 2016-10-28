# Function: escape

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

- __date__: Formats a date into the current locale, equivalent of PHP's [strftime](http://php.net/manual/en/function.strftime.php).
The ```extra``` argument is the date format.
- __number__: Formats a number, equivalent of PHP's [number_format](http://php.net/manual/en/function.number_format.php).
The ```extra``` argument is the number of decimals.

## Example

```
{$value = 15.987654321}
{$value|format("number")}
{$value|format("number", 5)}

{$timestamp = 1477376776}
{$timestamp|format("date")}
{$timestamp|format("date", "%F")}
```

will output:

```
15.99
15.98765

Tue Oct 25 00:45:10 2016
2016-10-25
```
