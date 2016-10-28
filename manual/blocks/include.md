# Blocks: include

Includes another template in the current context.

You can pass variables to the included template with the ```with``` keyword followed by an array or variables.

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
