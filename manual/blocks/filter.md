# Blocks: filter

Applies the filters to the output of this block. 
The filters have the same syntax like the ```|``` syntax you would use on variables.

## Syntax

```
{filter $filters}

{/filter}
```

## Example

```
{$name = "john doe"}
{filter capitalize|spaceless}

    <div>
        <p>Hello, my name is {$name}.</p>
    </div>
{/filter}
```

will output:

```html
<div><p>Hello, My Name Is John Doe.</p></div>
```

## See Also

- [capitalize](../functions/capitalize.md)
- [escape](../functions/escape.md)
- [lower](../functions/lower.md)
- [raw](../functions/raw.md)
- [replace](../functions/replace.md)
- [spaceless](../functions/spaceless.md)
- [trim](../functions/trim.md)
- [truncate](../functions/truncate.md)
- [upper](../functions/upper.md)
