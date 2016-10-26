# Blocks: capture

A ```capture``` block is used to capture the contents of a block into a variable.

## Syntax

```
{capture $variableName}
    
{/capture}
```

## Example

```
{$name = "John"}
{capture $title}
    This is my title for {$name}
{/capture}

{$title}
```

will output:

```
This is my title for John
```
