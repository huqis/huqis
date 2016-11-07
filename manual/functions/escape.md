# Function: escape

Escapes the provided string.

## Syntax

```escape($value, $format = "html")```

## Arguments

|Index|Name|Type|Required|Default|Description|
|---|---|---|---|---|---|
|0|value|string or array|yes|-|String to escape|
|1|format|string|no|html|Format of the escape|

### Formats

- __html__: Escapes all HTML characters, equivalent of PHP's [htmlspecialchars](http://php.net/manual/en/function.htmlspecialchars.php).
- __url__: Encodes a URL if the value is a string, equivalent of PHP's [rawurlencode](http://php.net/manual/en/function.rawurlencode.php). 
When the value is an array, a query string is generated using PHP's [http_build_query](http://php.net/manual/en/function.http_build_query.php). 
- __tags__: Strips the HTML tags from the provided string, equivalent of PHP's [striptags](http://php.net/manual/en/function.striptags.php). 
- __slug__: Creates a slug.

## Example

```
{$result = escape($string)}
{$result = escape($string, "html")}

{$result = $string|escape}
{$result = $string|escape("html")}
{$result = $url|escape("url")}
{$result = $title|escape("slug")}
```

## See Also

- [autoescape](../blocks/autoescape.md)
- [raw](raw.md)
