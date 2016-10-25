# Blocks: include

Includes another template in the current context.

## Syntax

```
{include $template[ with $variables]}
```

## Example

```
{include "another-template.tpl"}
{include "another-template.tpl" with ["name" = "John"]}
{include $template}
```


## See Also

- [extends](extends.md)
