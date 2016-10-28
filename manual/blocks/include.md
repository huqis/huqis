# Blocks: include

Includes another template in the current context.

You can pass variables to the included template with the ```with``` keyword followed by an array of variables.
Since you include another template in the current context, those variables will also be set after the include block.
If they are set before the ```include```, they are overwritten with the passed variables.


## Syntax

```
{include $template[ with $variables]}
```

## Example

```
{include "another-template.tpl"}
{include "another-template.tpl" with ["name" = "John"]}
{include "another-template.tpl" with $variables}
{include $template}
```

## See Also

- [extends](extends.md)
