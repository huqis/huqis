<?php

namespace frame\library;

use frame\library\block\TemplateBlock;
use frame\library\exception\RuntimeTemplateException;
use frame\library\func\TemplateFunction;
use frame\library\helper\ReflectionHelper;
use frame\library\operator\expression\ExpressionOperator;
use frame\library\operator\logical\LogicalOperator;
use frame\library\resource\TemplateResourceHandler;

/**
 * Context for the compile and runtime of a template. The context is the scope
 * of a running block and keeps all available variables, functions, blocks ...
 */
class TemplateContext {

    /**
     * Instance of the resource handler for the template engine
     * @var \frame\library\resource\TemplateResourceHandler
     */
    private $resourceHandler;

    /**
     * Instance of the reflection helper to obtain and set variables
     * @var \frame\library\helper\ReflectionHelper
     */
    private $reflectionHelper;

    /**
     * Parent context when a child is created
     * @var TemplateContext
     */
    private $parent;

    /**
     * Instance of the template engine
     * @var TemplateEngine
     */
    private $engine;

    /**
     * Flag to see if native PHP functions are included in the context
     * @var boolean
     */
    private $allowPhpFunctions;

    /**
     * Array with the available variables
     * @var array
     */
    private $variables;

    /**
     * Array with the available blocks
     * @var array
     */
    protected $blocks;

    /**
     * Array with the available functions
     * @var array
     */
    protected $functions;

    /**
     * Array with the available logical operators
     * @var array
     */
    private $logicalOperators;

    /**
     * Array with the available expression operators
     * @var array
     */
    private $expressionOperators;

    /**
     * Constructs a new template context
     * @param \frame\library\resource\TemplateResourceHandler $resourceHandler
     * @param \frame\library\helper\ReflectionHelper $reflectionHelper
     * @param \frame\library\TemplateContext $parent Parent context when
     * creating a child context
     * @return null
     * @throws \frame\library\exception\RuntimeTemplateException when no
     * resource handler is provided, nor directly, nor through the parent
     */
    public function __construct(TemplateResourceHandler $resourceHandler = null, ReflectionHelper $reflectionHelper = null, TemplateContext $parent = null) {
        $this->variables = [];
        $this->blocks = [];
        $this->functions = [];
        $this->logicalOperators = [];
        $this->expressionOperators = [];

        if ($parent === null) {
            // no parent context provided
            if ($resourceHandler === null) {
                throw new RuntimeTemplateException('Could not create template context: please provide a parent or a resource handler');
            }

            if ($reflectionHelper === null) {
                $reflectionHelper = new ReflectionHelper();
            }
        } else {
            // base on a parent context
            if ($resourceHandler === null) {
                $resourceHandler = $parent->getResourceHandler();
            }

            if ($reflectionHelper === null) {
                $reflectionHelper = $parent->getReflectionHelper();
            }

            $this->engine = $parent->getEngine();
            $this->allowPhpFunctions = $parent->allowsPhpFunctions();

            // start from the parent's setup
            $this->blocks = $parent->blocks;
            $this->functions = $parent->functions;
            $this->variables = $parent->variables;
            $this->logicalOperators = $parent->logicalOperators;
            $this->expressionOperators = $parent->expressionOperators;
        }

        $this->resourceHandler = $resourceHandler;
        $this->reflectionHelper = $reflectionHelper;
        $this->parent = $parent;
    }

    /**
     * Gets the resource handler
     * @return \frame\library\resource\TemplateResourceHandler
     */
    public function getResourceHandler() {
        return $this->resourceHandler;
    }

    /**
     * Gets the reflection helper
     * @return \frame\library\helper\ReflectionHelper
     */
    public function getReflectionHelper() {
        return $this->reflectionHelper;
    }

    /**
     * Sets whether native PHP functions are included in the function list. This
     * creates a fallback for functions which don't exist in the context.
     * @param boolean $allowPhpFunctions True to include the PHP functions,
     * false otherwise
     * @return null
     */
    public function setAllowPhpFunctions($allowPhpFunctions) {
        $this->allowPhpFunctions = $allowPhpFunctions;
    }

    /**
     * Gets whether native PHP functions are included in the function list
     * @return boolean True to include PHP functions, false otherwise
     * @see setAllowPhpFunctions
     * @see call
     */
    public function allowsPhpFunctions() {
        return $this->allowPhpFunctions;
    }

    /**
     * Sets the template engine. This is invoked by the engine itself and should
     * not be called manually.
     * @param \frame\library\TemplateEngine $engine Instance of the engine
     * @return null
     */
    public function setEngine(TemplateEngine $engine) {
        $this->engine = $engine;
    }

    /**
     * Gets the template engine
     * @return \frame\library\TemplateEngine
     */
    public function getEngine() {
        return $this->engine;
    }

    /**
     * Hook invoked before compiling
     * @return null
     */
    public function preCompile() {

    }

    //
    // SCOPE METHODS
    //

    /**
     * Creates a child context with this context as parent
     * @return \frame\library\TemplateContext
     */
    public function createChild() {
        return new static(null, null, $this);
    }

    /**
     * Gets the parent context from this child context
     * @param boolean $keepVariables Set to true to keep the variables from the
     * child context
     * @return \frame\library\TemplateContext|null Parent context when this is a
     * child context, null otherwise
     */
    public function getParent($keepVariables = false) {
        $parent = $this->parent;

        if ($parent) {
            if ($keepVariables) {
                $parent->setVariables($this->variables, false);
            }

            $parent->setFunctions($this->functions);
        }

        return $parent;
    }

    //
    // VARIABLE METHODS
    //

    /**
     * Sets multiple variables at once
     * @param array $variables Array with the name as key and the variable as
     * value
     * @param boolean $process Set to false to work without tokenizing the
     * variable name
     * @return null
     * @see setVariable
     */
    public function setVariables(array $variables, $process = true) {
        foreach ($variables as $variable => $value) {
            $this->setVariable($variable, $value, $process);
        }
    }

    /**
     * Sets a variable to the context
     * @param string $name Name of the variable. The name is tokenized on dot
     * (.) to handle nested arrays or objects. Setters can be used
     * transparantly using the property name instead of the method.
     * eg person.name could translate to $person->setName($value),
     * $person->name = $value or $person['name'] = $value depending on the value
     * of $person
     * @param mixed $value Value for the variable
     * @param boolean $process Set to false to work without tokenizing the
     * variable name
     * @return null
     */
    public function setVariable($name, $value, $process = true) {
        if ($process === false || strpos($name, '.') === false) {
            // no dotted name
            if ($value !== null) {
                $this->variables[$name] = $value;
            }

            return;
        }

        $tokens = explode('.', $name);
        $name = array_shift($tokens);

        if (!isset($this->variables[$name])) {
            // new variable
            if ($value !== null) {
                $this->variables[$name] = $this->createVariable($tokens, $value);
            }

            return;
        }

        // modify an existing variable
        $this->setVariableValue($this->variables[$name], $tokens, $value);
    }

    /**
     * Updates a variables value
     * @param array $variables Variables array to update
     * @param array $tokens Tokens of the property names
     * @param mixed $value Value to set
     * @return array Updated variables
     */
    private function setVariableValue(array &$variables, array $tokens, $value) {
        $property = array_pop($tokens);

        while ($token = array_shift($tokens)) {
            $newVariables = $this->reflectionHelper->getProperty($variables, $token);
            if ($newVariables === null) {
                // empty token occured, take over from here
                $tokens[] = $property;

                $this->reflectionHelper->setProperty($variables, $token, $this->createVariable($tokens, $value));

                $property = null;

                break;
            } elseif (is_array($newVariables)) {
                $tokens[] = $property;

                $this->reflectionHelper->setProperty($variables, $token, $this->setVariableValue($newVariables, $tokens, $value));

                $property = null;

                break;
            } elseif (!is_object($newVariables)) {
                throw new RuntimeTemplateException('Invalid value occured');
            }

            $variables = $newVariables;
        }

        if ($property) {
            $this->reflectionHelper->setProperty($variables, $property, $value);
        }

        return $variables;
    }

    /**
     * Creates a new hierarchic variable
     * @param array $tokens Tokens of the dotted name
     * @param mixed $value Value to set
     * @return array Hierarchic array with the provided value as last leaf
     */
    private function createVariable($tokens, $value) {
        $variable = [];

        $token = null;
        foreach ($tokens as $token) {
            $variable[$token] = [];
        }

        $variable[$token] = $value;

        return $variable;
    }

    /**
     * Gets a variable from the context
     * @param string $name Name of the variable. The name is tokenized on dot
     * (.) to handle nested arrays or objects. Getters can be used
     * transparantly using the property name instead of the method.
     * eg person.name could translate to $person->getName(), $person->name or
     * $person['name'] depending on the value of $person
     * @param boolean $process Set to false to work without tokenizing the
     * variable name
     * @param mixed $default Default value for when the variable is not set
     * @return null
     */
    public function getVariable($name, $process = false, $default = null) {
        if (strpos($name, '.') === false) {
            // no dotted name
            if (!isset($this->variables[$name])) {
                return $default;
            }

            return $this->variables[$name];
        }

        $tokens = explode('.', $name);

        // first token is actually in the variables array
        $name = array_shift($tokens);
        if (!isset($this->variables[$name])) {
            return $default;
        }

        // use reflection to obtain the other tokens
        $value = $this->variables[$name];
        foreach ($tokens as $token) {
            $value = $this->reflectionHelper->getProperty($value, $token);
            if ($value === null) {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Gets all variables in this context
     * @return array
     */
    public function getVariables() {
        return $this->variables;
    }

    /**
     * Removes all variables from this context
     * @return null
     */
    public function resetVariables() {
        $this->variables = [];
    }

    /**
     * Applies the provided modifiers on the provided value
     * @param mixed $value Value to apply the modifiers to
     * @param array $modifiers Array with modifier arrays as value. A modifier
     * array is an array with the name of the template function as first value
     * and the extra arguments for that function are the remaining values.
     * @return mixed Value with the modifiers applied
     * @see \frame\library\func\TemplateFunction
     * @see call
     */
    public function applyModifiers($value, array $modifiers) {
        foreach ($modifiers as $arguments) {
            $name = array_shift($arguments);
            array_unshift($arguments, $value);

            $value = $this->call($name, $arguments);
        }

        return $value;
    }

    //
    // BLOCK METHODS
    //

    /**
     * Sets a template block to this context
     * @param string $name Name of the block
     * @param \frame\library\block\TemplateBlock $block Instance of the block
     * @return null
     */
    public function setBlock($name, TemplateBlock $block) {
        $this->blocks[$name] = $block;
    }

    /**
     * Sets multiple blocks to this context at once
     * @param array $blocks Array with the name of the block as key and a block
     * instance as value
     * @return null
     * @see \frame\library\block\TemplateBlock
     */
    public function setBlocks(array $blocks) {
        foreach ($blocks as $name => $block) {
            $this->setBlock($name, $block);
        }
    }

    /**
     * Checks if the provided block is registered
     * @return boolean
     */
    public function hasBlock($name) {
        return isset($this->blocks[$name]);
    }

    /**
     * Gets the block with the provided name
     * @param string $name Name the block is registered with
     * @return \frame\library\block\TemplateBlock|null Instance of the block if
     * it's registered, null otherwise
     */
    public function getBlock($name) {
        if (!$this->hasBlock($name)) {
            return null;
        }

        return $this->blocks[$name];
    }

    /**
     * Removes a block from the context
     * @param string $name Name of the block
     * @return boolean True when the block is removed, false when it is not
     * registered
     */
    public function removeBlock($name) {
        if (!$this->hasBlock($name)) {
            return false;
        }

        unset($this->blocks[$name]);

        return true;
    }

    //
    // FUNCTION METHODS
    //

    /**
     * Sets a function to this context
     * @param string $name Name of the function as used in the templates
     * @param \frame\library\func\TemplateFunction $function Instance of the
     * function
     * @return null
     */
    public function setFunction($name, TemplateFunction $function) {
        $this->functions[$name] = $function;
    }

    /**
     * Sets multiple functions to this context at once
     * @param array $functions Array with the name of the function as key and a
     * function instance as value
     * @return null
     * @see \frame\library\func\TemplateFunction
     */
    public function setFunctions(array $functions) {
        foreach ($functions as $name => $function) {
            $this->setFunction($name, $function);
        }
    }

    /**
     * Gets the function with the provided name
     * @param string $name Name the function is registered with
     * @return \frame\library\func\TemplateFunction|null Instance of the
     * function if it's registered, null otherwise
     */
    public function getFunction($name) {
        if ($this->hasFunction($name)) {
            return $this->functions[$name];
        } else {
            return null;
        }
    }

    /**
     * Checks if the provided function is registered
     * @return boolean
     */
    public function hasFunction($name) {
        return isset($this->functions[$name]);
    }

    /**
     * Calls a function from the context. If the function name is not registered
     * and allow native PHP functions is enabled, the PHP function with the
     * provided name will be called
     * @param string $name Name of the function
     * @param array $arguments Arguments for the function
     * @return mixed Returning value of the function
     * @throws \frame\library\exception\RuntimeTemplateException when the
     * function does not exist
     * @see allowsPhpFunctions
     */
    public function call($name, array $arguments = []) {
        if (!$this->hasFunction($name)) {
            if ($this->allowPhpFunctions && function_exists($name)) {
                return call_user_func_array($name, $arguments);
            }

            throw new RuntimeTemplateException('Could not call ' . $name . ': function is not registered');
        }

        return $this->getFunction($name)->call($this, $arguments);
    }

    /**
     * Removes a function from the context
     * @param string $name Name of the function
     * @return boolean True when the function is removed, false when it is not
     * registered
     */
    public function removeFunction($name) {
        if (!$this->hasFunction($name)) {
            return false;
        }

        unset($this->functions[$name]);

        return true;
    }

    //
    // LOGICAL OPERATOR METHODS
    //

    /**
     * Sets a logical operator to this context
     * @param string $syntax Syntax of the operator
     * @param \frame\library\operator\logical\LogicalOperator $logicalOperator
     * Instance of the logical operator
     * @return null
     */
    public function setLogicalOperator($syntax, LogicalOperator $logicalOperator) {
        $this->logicalOperators[$syntax] = $logicalOperator;
    }

    /**
     * Gets the logical operator with the provided syntax
     * @param string $syntax Syntax of the operator
     * @return \frame\library\operator\logical\LogicalOperator|null Instance of
     * the logical operator if it's registered, null otherwise
     */
    public function getLogicalOperator($syntax) {
        if ($this->hasLogicalOperator($syntax)) {
            return $this->logicalOperators[$syntax];
        } else {
            return null;
        }
    }

    /**
     * Checks if the provided logical operator is registered
     * @param string $syntax Syntax of the operator
     * @return boolean
     */
    public function hasLogicalOperator($syntax) {
        return isset($this->logicalOperators[$syntax]);
    }

    /**
     * Gets all the available logical operators
     * @return array Array with the syntax of the operator as key and an
     * instance as value
     * @see \frame\library\operator\logical\LogicalOperator
     */
    public function getLogicalOperators() {
        return $this->logicalOperators;
    }

    /**
     * Removes a logical operator from the context
     * @param string $syntax Syntax of the operator
     * @return boolean True when the operator is removed, false when it is not
     * registered
     */
    public function removeLogicalOperator($syntax) {
        if (!$this->hasLogicalOperator($syntax)) {
            return false;
        }

        unset($this->logicalOperators[$syntax]);

        return true;
    }

    //
    // EXPRESSION OPERATOR METHODS
    //

    /**
     * Sets an expression operator to this context
     * @param string $syntax Syntax of the operator
     * @param \frame\library\operator\expression\ExpressionOperator $expressionOperator
     * Instance of the expression operator
     * @return null
     */
    public function setExpressionOperator($syntax, ExpressionOperator $expressionOperator) {
        $this->expressionOperators[$syntax] = $expressionOperator;
    }

    /**
     * Gets the expression operator with the provided syntax
     * @param string $syntax Syntax of the operator
     * @return \frame\library\operator\logical\LogicalOperator|null Instance of
     * the expression operator if it's registered, null otherwise
     */
    public function getExpressionOperator($syntax) {
        if ($this->hasExpressionOperator($syntax)) {
            return $this->expressionOperators[$syntax];
        } else {
            return null;
        }
    }

    /**
     * Checks if the provided expression operator is registered
     * @param string $syntax Syntax of the operator
     * @return boolean
     */
    public function hasExpressionOperator($syntax) {
        return isset($this->expressionOperators[$syntax]);
    }

    /**
     * Gets all the available expression operators
     * @return array Array with the syntax of the operator as key and an
     * instance as value
     * @see \frame\library\operator\expression\ExpressionOperator
     */
    public function getExpressionOperators() {
        return $this->expressionOperators;
    }

    /**
     * Removes a expression operator from the context
     * @param string $syntax Syntax of the operator
     * @return boolean True when the operator is removed, false when it is not
     * registered
     */
    public function removeExpressionOperator($syntax) {
        if (!$this->hasExpressionOperator($syntax)) {
            return false;
        }

        unset($this->expressionOperators[$syntax]);

        return true;
    }

}
