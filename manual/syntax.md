# Syntax

All template syntax expressions are delimited by ```{``` and ```}```.

## Variables

Strings can only be delimited by ```"```.

Arrays are delimited by ```[``` and ```]``` with a ```,``` between the elements.
To specify a key, use the assignment ```=``` operator.

You can unset any variable by assigning null to it:

```
{"test"}
{15.987}
{true}
{null}

{$variable}
{$variable = "test"}

{$array = ["John", "Jane"]}
{$array = ["key1" = "value1", "key2" = "value2"]}

{$array.key}

{$object.property}
{$object.method()}

{$array.key3.object = $object}

{$array.key3.object.method()}

{$variable = null}
{$array.key3 = null}
{$object = null}
```

## Blocks

Blocks are interpreted at compile time.

- [block](blocks/block.md)
- [break](blocks/break.md)
- [capture](blocks/capture.md)
- [continue](blocks/continue.md)
- [cycle](blocks/cycle.md)
- [extends](blocks/extends.md)
- [foreach](blocks/foreach.md)
- [function](blocks/function.md)
- [if](blocks/if.md)
- [include](blocks/include.md)
- [literal](blocks/literal.md)
- [macro](blocks/macro.md)
- [return](blocks/return.md)

## Functions

Functions are interpreted at runtime.

- [capitalize](functions/capitalize.md)
- [concat](functions/concat.md)
- [default](functions/default.md)
- [escape](functions/escape.md)
- [format](functions/format.md)
- [lower](functions/lower.md)
- [replace](functions/replace.md)
- [trim](functions/trim.md)
- [truncate](functions/truncate.md)
- [upper](functions/upper.md)

A function can also be used as a modifier with ```|``` after an expression.
The first argument will be the value it's modifying.

Both of the following expressions have the same result:

```
{capitalize($string, "first")}
{$string|capitalize("first")}
```

You can chain modifiers after each other:

```
{$string|lower|capitalize("all")}
```

## Operators

Check the overview of the available [operators](operators.md).

## Comments

Comments are delimited by ```{*``` and ```*}```.
