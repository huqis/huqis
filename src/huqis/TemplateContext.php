<?php

namespace huqis;

use huqis\block\TemplateBlock;
use huqis\exception\RuntimeTemplateException;
use huqis\func\TemplateFunction;
use huqis\helper\ReflectionHelper;
use huqis\operator\expression\ExpressionOperator;
use huqis\operator\logical\LogicalOperator;
use huqis\resource\TemplateResourceHandler;

/**
 * Context for the compile and runtime of a template. The context is the scope
 * of a running block and keeps all available variables, functions, blocks ...
 */
class TemplateContext {

    /**
     * Instance of the resource handler for the template engine
     * @var \huqis\resource\TemplateResourceHandler
     */
    private $resourceHandler;

    /**
     * Instance of the reflection helper to obtain and set variables
     * @var \huqis\helper\ReflectionHelper
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
     * Flag to see the output should be escaped automatically
     * @var string|boolean
     */
    private $autoEscape;

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
     * Array with the available private blocks, block who are not copied to the
     * child context
     * @var array
     */
    protected $privateBlocks;

    /**
     * Array with the available functions
     * @var array
     */
    protected $functions;

    /**
     * Array with the name of the output filter as key and an array with as
     * first value the name of the filter. All remaining values are the
     * arguments.
     * @var array
     */
    protected $outputFilters;

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
     * @param \huqis\resource\TemplateResourceHandler $resourceHandler
     * @param \huqis\helper\ReflectionHelper $reflectionHelper
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
        $this->logicalOperators = [];
        $this->expressionOperators = [];
        $this->variables = [];
        $this->blocks = [];
        $this->privateBlocks = [];
        $this->functions = [];
        $this->outputFilters = [];

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
            $this->outputFilters = $parent->outputFilters;
        }

        $this->resourceHandler = $resourceHandler;
        $this->reflectionHelper = $reflectionHelper;
        $this->parent = $parent;
    }

    /**
     * Gets the resource handler
     * @return \huqis\resource\TemplateResourceHandler
     */
    public function getResourceHandler() {
        return $this->resourceHandler;
    }

    /**
     * Gets the reflection helper
     * @return \huqis\helper\ReflectionHelper
     */
    public function getReflectionHelper() {
        return $this->reflectionHelper;
    }

    /**
     * Sets whether native PHP functions are included in the function list. 
     * This creates a fallback for functions which don't exist in the context.
     * @param boolean $allowPhpFunctions True to include the PHP functions,
     * false otherwise
     */
    public function setAllowPhpFunctions($allowPhpFunctions) {
        $this->allowPhpFunctions = $allowPhpFunctions;
    }

    /**
     * Gets whether native PHP functions are included in the function list
     * @return boolean True to include PHP functions, false otherwise
     * @see setAllowPhpFunctions
     * @see call()
     */
    public function allowsPhpFunctions() {
        return $this->allowPhpFunctions;
    }

    /**
     * Sets the auto escape flag
     * @param string|boolean Name of the escape format, true for the default
     * HTML format and false to disable
     */
    public function setAutoEscape($format) {
        if ($format === false) {
            $this->removeOutputFilter('auto-escape');
        } elseif ($format === true) {
            $this->setOutputFilter('auto-escape', 'escape');
        } elseif ($format) {
            $this->setOutputFilter('auto-escape', 'escape', array($format));
        }

        $this->autoEscape = $format;
    }

    /**
     * Gets the auto escape flag
     * @return string|boolean Name of the escape format, true when enabled with
     * the default HTML default and false when disabled
     */
    public function getAutoEscape() {
        return $this->autoEscape;
    }

    /**
     * Sets the template engine. This is invoked by the engine itself and should
     * not be called manually.
     * @param \huqis\TemplateEngine $engine Instance of the engine
     */
    public function setEngine(TemplateEngine $engine) {
        $this->engine = $engine;
    }

    /**
     * Gets the template engine
     * @return \huqis\TemplateEngine
     */
    public function getEngine() {
        return $this->engine;
    }

    /**
     * Hook invoked before compiling
     */
    public function preCompile() {

    }

    //
    // SCOPE METHODS
    //

    /**
     * Creates a child context with this context as parent
     * @return \huqis\TemplateContext
     */
    public function createChild() {
        return new static(null, null, $this);
    }

    /**
     * Gets the parent context from this child context
     * @param boolean $keepVariables Set to true to keep the variables from the
     * child context
     * @return \huqis\TemplateContext|null Parent context when this is a
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
     * @return mixed Value of the variable
     */
    public function setVariable($name, $value, $process = true) {
        if ($process === false || strpos($name, '.') === false) {
            // no dotted name
            if ($value !== null) {
                $this->variables[$name] = $value;
            } elseif (array_key_exists($name, $this->variables)) {
                unset($this->variables[$name]);
            }

            return $value;
        }

        $tokens = explode('.', $name);
        $name = array_shift($tokens);

        if (!isset($this->variables[$name])) {
            // new variable
            if ($value !== null) {
                $this->variables[$name] = $this->createVariable($tokens, $value);
            }

            return $value;
        }

        // modify an existing variable
        $this->setVariableValue($this->variables[$name], $tokens, $value);

        return $value;
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
     * @return mixed Value of the variable or the provided default value when 
     * the variable is not set.
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
     */
    public function resetVariables() {
        $this->variables = [];
    }

    /**
     * Applies the provided filters on the provided value
     * @param mixed $value Value to apply the filters to
     * @param array $filters Array with filter arrays as value. A filter array
     * is an array with the name of the template function as first value and the
     * extra arguments for that function as the remaining values.
     * @return mixed Value with the filters applied
     * @see \huqis\func\TemplateFunction
     * @see call()
     */
    public function applyFilters($value, array $filters) {
        foreach ($filters as $arguments) {
            $name = array_shift($arguments);
            array_unshift($arguments, $value);

            $value = $this->call($name, $arguments);
        }

        return $value;
    }

    /**
     * Ensures the provided value is an array
     * @param mixed $array Value to ensure
     * @param string $error Error message for the exception
     * @return array Provided array value
     * @throws \huqis\exception\RuntimeTemplateException when the
     * provided value is not an array
     */
    public function ensureArray($array, $error) {
        if (!is_array($array)) {
            throw new RuntimeTemplateException($error);
        }

        return $array;
    }

    /**
     * Ensures the provided value is an object
     * @param mixed $object Value to ensure
     * @param string $error Error message for the exception
     * @return mixed Provided object instance
     * @throws \huqis\exception\RuntimeTemplateException when the
     * provided value is not an object
     */
    public function ensureObject($object, $error) {
        if (!is_object($object)) {
            throw new RuntimeTemplateException($error);
        }

        return $object;
    }

    //
    // BLOCK METHODS
    //

    /**
     * Sets a template block to this context
     * @param string $name Name of the block
     * @param \huqis\block\TemplateBlock $block Instance of the block
     * @param boolean $isPrivate Set to true to keep this block in this context,
     * no copies are made to child contexts
     */
    public function setBlock($name, TemplateBlock $block, $isPrivate = false) {
        if ($isPrivate) {
            $this->privateBlocks[$name] = $block;
        } else {
            $this->blocks[$name] = $block;
        }
    }

    /**
     * Sets multiple blocks to this context at once
     * @param array $blocks Array with the name of the block as key and a block
     * instance as value
     * @see \huqis\block\TemplateBlock
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
        return isset($this->blocks[$name]) || isset($this->privateBlocks[$name]);
    }

    /**
     * Gets the block with the provided name
     * @param string $name Name the block is registered with
     * @return \huqis\block\TemplateBlock|null Instance of the block if
     * it's registered, null otherwise
     */
    public function getBlock($name) {
        if (isset($this->blocks[$name])) {
            return $this->blocks[$name];
        } elseif (isset($this->privateBlocks[$name])) {
            return $this->privateBlocks[$name];
        } else {
            return null;
        }
    }

    /**
     * Removes a block from the context
     * @param string $name Name of the block
     * @return boolean True when the block is removed, false when it is not
     * registered
     */
    public function removeBlock($name) {
        if (isset($this->blocks[$name])) {
            unset($this->blocks[$name]);

            return true;
        } elseif (isset($this->privateBlocks[$name])) {
            unset($this->privateBlocks[$name]);

            return true;
        } else {
            return false;
        }
    }

    //
    // FUNCTION METHODS
    //

    /**
     * Sets a function to this context
     * @param string $name Name of the function as used in the templates
     * @param \huqis\func\TemplateFunction $function Instance of the
     * function
     */
    public function setFunction($name, TemplateFunction $function) {
        $this->functions[$name] = $function;
    }

    /**
     * Sets multiple functions to this context at once
     * @param array $functions Array with the name of the function as key and a
     * function instance as value
     * @see \huqis\func\TemplateFunction
     */
    public function setFunctions(array $functions) {
        foreach ($functions as $name => $function) {
            $this->setFunction($name, $function);
        }
    }

    /**
     * Gets the function with the provided name
     * @param string $name Name the function is registered with
     * @return \huqis\func\TemplateFunction|null Instance of the
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

    /**
     * Calls a function from the context. If the function name is not registered
     * and allow native PHP functions is enabled, the PHP function with the
     * provided name will be called
     * @param string $name Name of the function
     * @param array $arguments Arguments for the function
     * @return mixed Returning value of the function
     * @throws \huqis\exception\RuntimeTemplateException when the
     * function does not exist
     * @see allowsPhpFunctions
     */
    public function call($name, array $arguments = []) {
        if (!$this->hasFunction($name)) {
            if ($this->allowPhpFunctions && function_exists($name)) {
                return call_user_func_array($name, $arguments);
            }

            throw new RuntimeTemplateException('Could not call function ' . $name . ': function is not registered');
        }

        return $this->getFunction($name)->call($this, $arguments);
    }

    //
    // OUTPUT FILTER METHODS
    //

    /**
     * Sets an output filter
     * @param string $name Name of the output filter
     * @param string $function Name of the function to call
     * @param array $arguments The value to be filtered is the first argument
     * for the function, set this to the remaining arguments
     * @return boolean True if the output filter is set, false if it's already
     * set under the provided or another name
     */
    public function setOutputFilter($name, $function, array $arguments = null) {
        if (!$arguments) {
            $arguments = [];
        }

        array_unshift($arguments, $function);

        foreach ($this->outputFilters as $filterName => $filterArguments) {
            if ($filterArguments == $arguments) {
                return false;
            }
        }

        $this->outputFilters[$name] = $arguments;

        return true;
    }

    /**
     * Gets the output filters
     * @return array Array with the name of the output filter as key and an
     * array with as first value the name of the filter. All remaining values
     * are the arguments.
     * @see applyFilters
     */
    public function getOutputFilters() {
        return $this->outputFilters;
    }

    /**
     * Removes an output filter from the context
     * @param string $name Name of the output filter
     * @return boolean True when the output filter is removed, false when it is
     * not registered
     */
    public function removeOutputFilter($name) {
        if (!isset($this->outputFilters[$name])) {
            return false;
        }

        unset($this->outputFilters[$name]);

        return true;
    }

    //
    // LOGICAL OPERATOR METHODS
    //

    /**
     * Sets a logical operator to this context
     * @param string $syntax Syntax of the operator
     * @param \huqis\operator\logical\LogicalOperator $logicalOperator
     * Instance of the logical operator
     */
    public function setLogicalOperator($syntax, LogicalOperator $logicalOperator) {
        $this->logicalOperators[$syntax] = $logicalOperator;
    }

    /**
     * Gets the logical operator with the provided syntax
     * @param string $syntax Syntax of the operator
     * @return \huqis\operator\logical\LogicalOperator|null Instance of
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
     * @see \huqis\operator\logical\LogicalOperator
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
     * @param \huqis\operator\expression\ExpressionOperator $expressionOperator
     * Instance of the expression operator
     */
    public function setExpressionOperator($syntax, ExpressionOperator $expressionOperator) {
        $this->expressionOperators[$syntax] = $expressionOperator;
    }

    /**
     * Gets the expression operator with the provided syntax
     * @param string $syntax Syntax of the operator
     * @return \huqis\operator\logical\LogicalOperator|null Instance of
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
     * @see \huqis\operator\expression\ExpressionOperator
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
