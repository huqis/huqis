# Blocks: literal 

The block contents are rendered literal, no template syntax is interpreted.

## Syntax

```
{literal}
    ...
{/literal}
```

## Example

```
{literal}
{$value1 = 7}
{$value2 = 12}
{/literal}
```

will output:

```
{$value1 = 7}
{$value2 = 12}
```

