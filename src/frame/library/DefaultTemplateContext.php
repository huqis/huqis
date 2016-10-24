<?php

namespace frame\library;

use frame\library\block\AssignTemplateBlock;
use frame\library\block\BlockTemplateBlock;
use frame\library\block\CallTemplateBlock;
use frame\library\block\CycleTemplateBlock;
use frame\library\block\ExtendsTemplateBlock;
use frame\library\block\ForeachTemplateBlock;
use frame\library\block\IfTemplateBlock;
use frame\library\block\IncludeTemplateBlock;
use frame\library\block\LiteralTemplateBlock;
use frame\library\block\MacroTemplateBlock;
use frame\library\func\CapitalizeTemplateFunction;
use frame\library\func\ConcatTemplateFunction;
use frame\library\func\EscapeTemplateFunction;
use frame\library\func\ExtendTemplateFunction;
use frame\library\func\IncludeTemplateFunction;
use frame\library\func\ReplaceTemplateFunction;
use frame\library\func\TruncateTemplateFunction;
use frame\library\operator\expression\AssignExpressionOperator;
use frame\library\operator\expression\GenericExpressionOperator;
use frame\library\operator\expression\RegexExpressionOperator;
use frame\library\operator\logical\GenericLogicalOperator;
use frame\library\resource\TemplateResourceHandler;
use frame\library\ReflectionHelper;

/**
 * Template context with the default syntax setup
 */
class DefaultTemplateContext extends TemplateContext {

    /**
     * Constructs a new template context
     * @param \frame\library\resource\TemplateResourceHandler $resourceHandler
     * @param \frame\library\ReflectionHelper $reflectionHelper
     * @param \frame\library\TemplateContext $parent Parent context when
     * creating a child context
     * @return null
     * @throws \frame\library\exception\RuntimeTemplateException when no
     * resource handler is provided, nor directly, nor through the parent
     */
    public function __construct(TemplateResourceHandler $resourceHandler = null, ReflectionHelper $reflectionHelper = null, TemplateContext $parent = null) {
        parent::__construct($resourceHandler, $reflectionHelper, $parent);

        if ($parent !== null) {
            return;
        }

        $this->setAllowPhpFunctions(false);

        $andLogicalOperator = new GenericLogicalOperator('and');
        $orLogicalOperator = new GenericLogicalOperator('or');

        $this->setLogicalOperator(' and ', $andLogicalOperator);
        $this->setLogicalOperator('&&', $andLogicalOperator);
        $this->setLogicalOperator(' or ', $orLogicalOperator);
        $this->setLogicalOperator('||', $orLogicalOperator);
        $this->setLogicalOperator(' xor ', new GenericLogicalOperator('xor'));

        $this->setExpressionOperator('=', new AssignExpressionOperator());
        $this->setExpressionOperator('~', new GenericExpressionOperator('.'));
        $this->setExpressionOperator('+', new GenericExpressionOperator('+'));
        $this->setExpressionOperator('-', new GenericExpressionOperator('-'));
        $this->setExpressionOperator('*', new GenericExpressionOperator('*'));
        $this->setExpressionOperator('/', new GenericExpressionOperator('/'));
        $this->setExpressionOperator('%', new GenericExpressionOperator('%'));
        $this->setExpressionOperator('===', new GenericExpressionOperator('==='));
        $this->setExpressionOperator('==', new GenericExpressionOperator('=='));
        $this->setExpressionOperator('!==', new GenericExpressionOperator('!=='));
        $this->setExpressionOperator('!=', new GenericExpressionOperator('!='));
        $this->setExpressionOperator('>=', new GenericExpressionOperator('>='));
        $this->setExpressionOperator('>', new GenericExpressionOperator('>'));
        $this->setExpressionOperator('<=', new GenericExpressionOperator('<='));
        $this->setExpressionOperator('<', new GenericExpressionOperator('<'));
        $this->setExpressionOperator('~=', new RegexExpressionOperator());

        $this->setFunction('capitalize', new CapitalizeTemplateFunction());
        $this->setFunction('concat', new ConcatTemplateFunction());
        $this->setFunction('escape', new EscapeTemplateFunction());
        $this->setFunction('_extends', new ExtendTemplateFunction());
        $this->setFunction('_include', new IncludeTemplateFunction());
        $this->setFunction('replace', new ReplaceTemplateFunction());
        $this->setFunction('truncate', new TruncateTemplateFunction());

        $this->setBlock('assign', new AssignTemplateBlock());
        $this->setBlock('block', new BlockTemplateBlock());
        $this->setBlock('call', new CallTemplateBlock());
        $this->setBlock('cycle', new CycleTemplateBlock());
        $this->setBlock('extends', new ExtendsTemplateBlock());
        $this->setBlock('foreach', new ForeachTemplateBlock());
        $this->setBlock('if', new IfTemplateBlock());
        $this->setBlock('include', new IncludeTemplateBlock());
        $this->setBlock('literal', new LiteralTemplateBlock());
        $this->setBlock('macro', new MacroTemplateBlock());
    }

}
