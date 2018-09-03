# Syntax

All template syntax expressions are delimited by ```{``` and ```}```.

## Variables

```
{"test"}
{15.987}
{true}
{null}
```

Strings can be delimited by a ```"``` or a ```'```.

Variables are prefixed with a ```$``` and set with the assignment ```=``` operator.

```
{$variable}
{$variable = "test"}
```

Arrays are delimited by ```[``` and ```]``` with a ```,``` between the elements.
To specify a key, use the assignment ```=``` operator.

```
{$array = ["John", "Jane"]}
{$array = ["key1" = "value1", "key2" = $variable]}
```

When accessing variables, you can use ```.``` do go deeper in a structural variable like arrays and objects.
This is also used to call methods on objects.

The following expressions both have the same result:

```
{$array.key1}
{$array["key1"]}

{$object.property}
{$object.getProperty()}
```

You can go as deep as you want with this.

```
{$array.key3.object = $object}
{$array.key3.object.method()}
```

You can unset any variable by assigning ```null``` to it:

```
{$variable = null}
{$array.key3 = null}
{$object = null}
```

## Blocks

Blocks provide the logical syntax of the template language.
They are interpreted at compile time.

The following blocks are available out of the box:

- [autoescape](blocks/autoescape.md)
- [block](blocks/block.md)
- [break](blocks/break.md)
- [capture](blocks/capture.md)
- [continue](blocks/continue.md)
- [cycle](blocks/cycle.md)
- [extends](blocks/extends.md)
- [filter](blocks/filter.md)
- [foreach](blocks/foreach.md)
- [function](blocks/function.md)
- [if](blocks/if.md)
- [include](blocks/include.md)
- [literal](blocks/literal.md)
- [macro](blocks/macro.md)
- [parent](blocks/parent.md)
- [return](blocks/return.md)

A block can only be defined in the engine by [adding it to the context](extend.md).

## Functions

Functions provide the extendability of the template language.
They are interpreted at runtime.

The following functions are available out of the box:

- [capitalize](functions/capitalize.md)
- [concat](functions/concat.md)
- [default](functions/default.md)
- [escape](functions/escape.md)
- [format](functions/format.md)
- [lower](functions/lower.md)
- [raw](functions/raw.md)
- [replace](functions/replace.md)
- [spaceless](functions/spaceless.md)
- [trim](functions/trim.md)
- [truncate](functions/truncate.md)
- [upper](functions/upper.md)

A function can be defined in a template using the [function block](blocks/function.md) or in the engine by [adding it to the context](extend.md).

### Filters

A function can be called directly but it can also be used as a filter by using the pipe ```|``` after an expression.
The first argument for the function will be the result of the expression.

Knowing this, both of the following expressions have the same result:

```
{capitalize($string, "first")}
{$string|capitalize("first")}
```

Filters are interesting because they can be chained after each other:

```
{$string|lower|capitalize("all")}
```

You can use the [filter block](blocks/filter.md) to apply this on whole blocks of code.

## Operators

Check the overview of the available [operators](operators.md).

## Comments

Comments are delimited by ```{*``` and ```*}```.
