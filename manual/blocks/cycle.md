# Blocks: cycle

Cycles the provided values everytime this block is encountered.

## Syntax

```
{cycle $values}
```

## Example

```
{$names = ["John", "Jane", "Mike"]}
<table>
{foreach $names as $name}
    <tr class="{cycle ["light", "dark"]}">
        <td>{$name}</td>
    </tr>
{/foreach}
</table>
```

will output:

```
<table>
    <tr class="light">
        <td>John</td>
    </tr>
    <tr class="dark">
       <td>Jane</td>
    </tr>
    <tr class="light">
        <td>Mike</td>
    </tr>
</table>
```
