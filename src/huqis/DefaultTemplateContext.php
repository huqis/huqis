<?php

namespace huqis;

use huqis\block\AutoEscapeTemplateBlock;
use huqis\block\BlockTemplateBlock;
use huqis\block\CaptureTemplateBlock;
use huqis\block\CycleTemplateBlock;
use huqis\block\ExtendsTemplateBlock;
use huqis\block\FilterTemplateBlock;
use huqis\block\ForeachTemplateBlock;
use huqis\block\FunctionTemplateBlock;
use huqis\block\IfTemplateBlock;
use huqis\block\IncludeTemplateBlock;
use huqis\block\LiteralTemplateBlock;
use huqis\block\MacroTemplateBlock;
use huqis\func\CapitalizeTemplateFunction;
use huqis\func\ConcatTemplateFunction;
use huqis\func\DefaultTemplateFunction;
use huqis\func\EscapeTemplateFunction;
use huqis\func\ExtendTemplateFunction;
use huqis\func\FormatTemplateFunction;
use huqis\func\IncludeTemplateFunction;
use huqis\func\LowerTemplateFunction;
use huqis\func\ReplaceTemplateFunction;
use huqis\func\SpacelessTemplateFunction;
use huqis\func\TrimTemplateFunction;
use huqis\func\TruncateTemplateFunction;
use huqis\func\UpperTemplateFunction;
use huqis\operator\expression\AssignExpressionOperator;
use huqis\operator\expression\GenericExpressionOperator;
use huqis\operator\expression\RegexExpressionOperator;
use huqis\operator\logical\GenericLogicalOperator;
use huqis\resource\TemplateResourceHandler;
use huqis\ReflectionHelper;

/**
 * Template context with the default syntax setup
 */
class DefaultTemplateContext extends TemplateContext {

    /**
     * Constructs a new template context
     * @param \huqis\resource\TemplateResourceHandler $resourceHandler
     * @param \huqis\ReflectionHelper $reflectionHelper
     * @param \huqis\TemplateContext $parent Parent context when
     * creating a child context
     * @throws \huqis\exception\RuntimeTemplateException when no
     * resource handler is provided, nor directly, nor through the parent
     */
    public function __construct(
        TemplateResourceHandler $resourceHandler = null, 
        ReflectionHelper $reflectionHelper = null, 
        TemplateContext $parent = null
    ) {
        parent::__construct($resourceHandler, $reflectionHelper, $parent);

        if ($parent !== null) {
            return;
        }

        $this->setAllowPhpFunctions(false);
        $this->setAutoEscape(true);
    }

    /**
     * Hook invoked before compiling
     */
    public function preCompile() {
        $this->ensureExpressions();
    }

    /**
     * Ensure all expressions exist in this container
     */
    protected function ensureExpressions() {
        if ($this->hasLogicalOperator(' and ')) {
            // expressions are already registered
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
     * Checks if the provided function is available
     * @param string $name Name of the function
     * @return boolean
     */
    public function hasFunction($name) {
        if (parent::hasFunction($name)) {
            return true;
        }

        return $this->ensureFunction($name);
    }

    /**
     * Ensures the function exists by loading it
     * @param $name Name of the function
     * @return boolean True when the function is loaded, false otherwise
     */
    protected function ensureFunction($name) {
        switch ($name) {
            case 'capitalize':
                $this->setFunction('capitalize', new CapitalizeTemplateFunction());

                return true;
            case 'concat':
                $this->setFunction('concat', new ConcatTemplateFunction());

                return true;
            case 'default':
                $this->setFunction('default', new DefaultTemplateFunction());

                return true;
            case 'escape':
                $this->setFunction('escape', new EscapeTemplateFunction());

                return true;
            case '_extends':
                $this->setFunction('_extends', new ExtendTemplateFunction());

                return true;
            case 'format':
                $this->setFunction('format', new FormatTemplateFunction());

                return true;
            case '_include':
                $this->setFunction('_include', new IncludeTemplateFunction());

                return true;
            case 'lower':
                $this->setFunction('lower', new LowerTemplateFunction());

                return true;
            case 'replace':
                $this->setFunction('replace', new ReplaceTemplateFunction());

                return true;
            case 'spaceless':
                $this->setFunction('spaceless', new SpacelessTemplateFunction());

                return true;
            case 'trim':
                $this->setFunction('trim', new TrimTemplateFunction());

                return true;
            case 'truncate':
                $this->setFunction('truncate', new TruncateTemplateFunction());

                return true;
            case 'upper':
                $this->setFunction('upper', new UpperTemplateFunction());

                return true;
        }

        return false;
    }

    /**
     * Checks if the provided block is available
     * @return boolean
     */
    public function hasBlock($name) {
        if (parent::hasBlock($name)) {
            return true;
        }

        return $this->ensureBlock($name);
    }

    /**
     * Ensures the block exists by loading it
     * @param $name Name of a block
     * @return boolean True when the block is loaded, false otherwise
     */
    protected function ensureBlock($name) {
        switch ($name) {
            case 'autoescape':
                $this->setBlock('autoescape', new AutoEscapeTemplateBlock());

                return true;
            case 'block':
                $this->setBlock('block', new BlockTemplateBlock());

                return true;
            case 'capture':
                $this->setBlock('capture', new CaptureTemplateBlock());

                return true;
            case 'cycle':
                $this->setBlock('cycle', new CycleTemplateBlock());

                return true;
            case 'extends':
                $this->setBlock('extends', new ExtendsTemplateBlock());

                return true;
            case 'filter':
                $this->setBlock('filter', new FilterTemplateBlock());

                return true;
            case 'foreach':
                $this->setBlock('foreach', new ForeachTemplateBlock());

                return true;
            case 'function':
                $this->setBlock('function', new FunctionTemplateBlock());

                return true;
            case 'if':
                $this->setBlock('if', new IfTemplateBlock());

                return true;
            case 'include':
                $this->setBlock('include', new IncludeTemplateBlock());

                return true;
            case 'literal':
                $this->setBlock('literal', new LiteralTemplateBlock());

                return true;
            case 'macro':
                $this->setBlock('macro', new MacroTemplateBlock());

                return true;
        }

        return false;
    }

}
