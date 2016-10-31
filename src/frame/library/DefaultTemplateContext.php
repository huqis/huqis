<?php

namespace frame\library;

use frame\library\block\BlockTemplateBlock;
use frame\library\block\CaptureTemplateBlock;
use frame\library\block\CycleTemplateBlock;
use frame\library\block\ExtendsTemplateBlock;
use frame\library\block\ForeachTemplateBlock;
use frame\library\block\FunctionTemplateBlock;
use frame\library\block\IfTemplateBlock;
use frame\library\block\IncludeTemplateBlock;
use frame\library\block\LiteralTemplateBlock;
use frame\library\block\MacroTemplateBlock;
use frame\library\func\CapitalizeTemplateFunction;
use frame\library\func\ConcatTemplateFunction;
use frame\library\func\DefaultTemplateFunction;
use frame\library\func\EscapeTemplateFunction;
use frame\library\func\ExtendTemplateFunction;
use frame\library\func\FormatTemplateFunction;
use frame\library\func\IncludeTemplateFunction;
use frame\library\func\LowerTemplateFunction;
use frame\library\func\ReplaceTemplateFunction;
use frame\library\func\TrimTemplateFunction;
use frame\library\func\TruncateTemplateFunction;
use frame\library\func\UpperTemplateFunction;
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
        $this->setAutoEscape(true);
    }

    /**
     * Hook invoked before compiling
     * @return null
     */
    public function preCompile() {
        $this->ensureExpressions();
    }

    /**
     * Ensure all expressions exist
     * @return null
     */
    protected function ensureExpressions() {
        if ($this->hasLogicalOperator(' and ')) {
            return;
        }

        $this->setLogicalOperator(' and ', new GenericLogicalOperator('and'));
        $this->setLogicalOperator('&&', new GenericLogicalOperator('and'));
        $this->setLogicalOperator(' or ', new GenericLogicalOperator('or'));
        $this->setLogicalOperator('||', new GenericLogicalOperator('or'));
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
    }

    /**
     * Checks if the provided function is registered
     * @return boolean
     */
    public function hasFunction($name) {
        if (parent::hasFunction($name)) {
            return true;
        }

        $this->ensureFunction($name);

        return parent::hasFunction($name);
    }

    /**
     * Ensures the function exists
     * @param $name Name of a default function
     * @return null
     */
    protected function ensureFunction($name) {
        switch ($name) {
            case 'capitalize':
                $this->setFunction('capitalize', new CapitalizeTemplateFunction());

                break;
            case 'concat':
                $this->setFunction('concat', new ConcatTemplateFunction());

                break;
            case 'default':
                $this->setFunction('default', new DefaultTemplateFunction());

                break;
            case 'escape':
                $this->setFunction('escape', new EscapeTemplateFunction());

                break;
            case '_extends':
                $this->setFunction('_extends', new ExtendTemplateFunction());

                break;
            case 'format':
                $this->setFunction('format', new FormatTemplateFunction());

                break;
            case '_include':
                $this->setFunction('_include', new IncludeTemplateFunction());

                break;
            case 'lower':
                $this->setFunction('lower', new LowerTemplateFunction());

                break;
            case 'replace':
                $this->setFunction('replace', new ReplaceTemplateFunction());

                break;
            case 'trim':
                $this->setFunction('trim', new TrimTemplateFunction());

                break;
            case 'truncate':
                $this->setFunction('truncate', new TruncateTemplateFunction());

                break;
            case 'upper':
                $this->setFunction('upper', new UpperTemplateFunction());

                break;
        }
    }

    /**
     * Checks if the provided block is registered
     * @return boolean
     */
    public function hasBlock($name) {
        if (parent::hasBlock($name)) {
            return true;
        }

        $this->ensureBlock($name);

        return parent::hasBlock($name);
    }

    /**
     * Ensures the block exists
     * @param $name Name of a default block
     * @return null
     */
    protected function ensureBlock($name) {
        switch ($name) {
            case 'block':
                $this->setBlock('block', new BlockTemplateBlock());

                break;
            case 'capture':
                $this->setBlock('capture', new CaptureTemplateBlock());

                break;
            case 'cycle':
                $this->setBlock('cycle', new CycleTemplateBlock());

                break;
            case 'extends':
                $this->setBlock('extends', new ExtendsTemplateBlock());

                break;
            case 'foreach':
                $this->setBlock('foreach', new ForeachTemplateBlock());

                break;
            case 'function':
                $this->setBlock('function', new FunctionTemplateBlock());

                break;
            case 'if':
                $this->setBlock('if', new IfTemplateBlock());

                break;
            case 'include':
                $this->setBlock('include', new IncludeTemplateBlock());

                break;
            case 'literal':
                $this->setBlock('literal', new LiteralTemplateBlock());

                break;
            case 'macro':
                $this->setBlock('macro', new MacroTemplateBlock());

                break;
        }
    }

}
