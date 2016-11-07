# Blocks: autoescape

An ```autoescape``` block can enable (or disable) output filtering on a specific block.

## Syntax

```
{autoescape[ $format]}
    
{/capture}
```

## Example

```
{$variable = "<variable>"}
{$not = "<not>"}

{autoescape}
    This is an escaped {$variable}, while this is {$not|raw}.
{/autoescape}

{autoescape true}{/autoescape}

{autoescape false}{/autoescape}

{autoescape "html"}{/autoescape}
```

will output:

```
This is an escaped &lt;variable&gt;, while this is <not>.
```

## See Also

- [escape](../functions/escape.md)
- [raw](../functions/raw.md)
