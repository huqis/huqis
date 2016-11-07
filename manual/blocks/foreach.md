# Blocks: foreach

Loops over an array or iterator.

Use the ```as``` keyword to define the variable for the value of the current iteration.
The ```key``` keyword defines the variable for the key in the array.
The ```loop``` keyword defines a variable with more information about the current iteration.

All these keywords are optional but at least one is required.
The sequence of the keywords do not matter.

The ```$loop``` variable is an array with the following keys:

- ```index```: Index of the current iteration
- ```revindex```: Reversed index counting from the end of the loop
- ```first```: Boolean to see if it's the first iteration
- ```last```: Boolean to see if it's the last iteration
- ```length```: Number of iterations

## Syntax

```
{foreach $array[as $value][ key $key][ loop $loop]}
    ...
{/foreach}
```

## Example

```
{$values = [1, 2, 3, 4, 5, 6, 7, 8, 9]}
{foreach $values as $value}
    {if $value == 3}
        {break}
    {/if}
    {$value}<br>
{/foreach}

{foreach $values key $key}{/foreach}

{foreach $values loop $loop}
    {if $loop.last}
        {$loop.index}<br>
    {/if}
{/foreach}

{foreach $values as $value key $key loop $loop}
    {if $loop.first}
        {$loop.index}<br>
    {/if}
{/foreach}

```

will output:

```
1
2
8
0
```

## See Also

- [break](break.md)
- [continue](continue.md)
- [cycle](cycle.md)
