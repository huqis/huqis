# Extending The Engine

You can extend the engine by adding new blocks, functions and operators to your context.

```php
<?php

use frame\library\block\LiteralTemplateBlock;
use frame\library\func\TruncateTemplateFunction;
use frame\library\operator\expression\RegexExpressionOperator;
use frame\library\operator\logical\GenericLogicalOperator;
use frame\library\DefaultTemplateContext;

$context = new DefaultTemplateContext($chainResourceHandler);
$context->setLogicalOperator(' xor ', new GenericLogicalOperator('xor'));
$context->setExpressionOperator('~=', new RegexExpressionOperator());
$context->setFunction('truncate', new TruncateTemplateFunction());
$context->setBlock('literal', new LiteralTemplateBlock());
```

Check the sources of the existing blocks, functions and operators to get you on your way.

## TemplateBlock

The ```TemplateBlock``` interface is used to implement custom template blocks.

Implementations out of the box:

- ```BlockTemplateBlock```
- ```BreakTemplateBlock```
- ```CaptureTemplateBlock```
- ```ContinueTemplateBlock```
- ```CycleTemplateBlock```
- ```ElseIfTemplateBlock```
- ```ElseTemplateBlock```
- ```ExtendsTemplateBlock```
- ```ForeachTemplateBlock```
- ```FunctionTemplateBlock```
- ```IfTemplateBlock```
- ```IncludeTemplateBlock```
- ```LiteralTemplateBlock```
- ```MacroTemplateBlock```
- ```ReturnTemplateBlock```

## TemplateFunction

The ```TemplateFunction``` interface is used to implement custom template functions.
Template functions can also be used as a modifier.

Implementations out of the box:

- ```CapitalizeTemplateFunction```
- ```ConcatTemplateFunction```
- ```DefaultTemplateFunction```
- ```EscapeTemplateFunction```
- ```FormatTemplateFunction```
- ```LowerTemplateFunction```
- ```ReplaceTemplateFunction```
- ```TrimTemplateFunction```
- ```TruncateTemplateFunction```
- ```UpperTemplateFunction```

## ExpressionOperator

The ```ExpressionOperator``` interface is used to implement template expression operators like +, -, <, ==, ...

Implementations out of the box:

- ```AssignExpressionOperator```
- ```GenericExpressionOperator```
- ```RegexExpressionOperator```

## LogicalOperator

The ```LogicalOperator``` interface is used to implement template logical operators like and, &&, or, ...

Implementations out of the box:

- ```GenericLogicalOperator```
