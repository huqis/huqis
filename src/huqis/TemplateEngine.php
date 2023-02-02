<?php

namespace huqis;

use huqis\cache\TemplateCache;
use huqis\exception\CompileTemplateException;
use huqis\exception\RuntimeTemplateException;
use huqis\executor\EvalTemplateExecutor;
use huqis\executor\IncludeTemplateExecutor;
use huqis\helper\StringHelper;
use huqis\resource\TemplateResourceHandler;

use \Exception;

/**
 * Main facade to the template engine
 */
class TemplateEngine {

    /**
     * Initial context for a render
     * @var \huqis\TemplateContext
     */
    private $context;

    /**
     * Instance of the template compiler
     * @var \huqis\TemplateCompiler
     */
    private $compiler;

    /**
     * Instance of the template executor
     * @var \huqis\executor\TemplateExecutor
     */
    private $executor;

    /**
     * Cache for the compiled templates
     * @var \huqis\cache\TemplateCache
     */
    private $cache;

    /**
     * Compile id of the template
     * @var string
     */
    private $compileId;

    /**
     * Flag to see if debug mode is on
     * @var boolean
     */
    private $isDebug;

    /**
     * Constructs a new template engine
     * @param \huqis\TemplateContext $context Initial context for a
     * render
     * @param \huqis\cache\TemplateCache $cache Instance of the cache
     * @return null
     */
    public function __construct(TemplateContext $context, TemplateCache $cache = null) {
        $this->context = $context;
        $this->context->setEngine($this);

        $this->compiler = new TemplateCompiler($this->context);

        $this->setIsDebug(false);
        $this->setCache($cache);
    }

    /**
     * Gets the initial context for a render
     * @return \huqis\TemplateContext
     */
    public function getContext() {
        return $this->context;
    }

    /**
     * Sets the cache for the compiled templates
     * @param \huqis\cache\TemplateCache $cache Instance of the cache
     * @return null
     */
    public function setCache(TemplateCache $cache = null) {
        $this->cache = $cache;
    }

    /**
     * Sets the compile id of the template, this influences the cache key of the
     * templates
     * @param string $compileId A suffix for the resource compile id
     * @return null
     */
    public function setCompileId($compileId) {
        $this->compileId = $compileId;
    }

    /**
     * Gets the current compile id
     * @return string
     */
    public function getCompileId() {
        return $this->compileId;
    }

    /**
     * Sets the debug flag. When debugging is enabled, templates will be
     * executed through a temporary file so a runtime exception can show the
     * source of the compiled template. When debugging is disabled, templates
     * will be executed through the eval function and the cache becomes lazy and
     * will not check any modification times.
     * @param boolean $isDebug True to enable debug
     * @return null
     */
    public function setIsDebug($isDebug) {
        if ($isDebug) {
            $this->executor = new IncludeTemplateExecutor();
        } else {
            $this->executor = new EvalTemplateExecutor();
        }

        $this->isDebug = $isDebug;
    }

    /**
     * Gets the debug flag
     * @return boolean
     * @see setIsDebug
     */
    public function isDebug() {
        return $this->isDebug;
    }

    /**
     * Renders a template resource
     * @param string $resource Name of the template resource
     * @param array $variables Variables assigned to the template
     * @param \huqis\TemplateContext $context Initial context
     * @param string $extends Template code from a dynamic extends block, used
     * by the compiler
     * @return string Rendered template
     * @throws \huqis\exception\TemplateException when a compile or
     * runtime error occured
     */
    public function render($resource, array $variables = null, TemplateContext $context = null, $extends = null) {
        $code = $this->compile($resource, $context, $runtimeId, $extends);

        if ($variables) {
            $context->setVariables($variables, false);
        }

        try {
            $output = $this->execute($context, $code, $runtimeId);
        } catch (Exception $exception) {
            $this->extractResourceFromException($exception, $code, $resource, $lineNumber);

            $exception = new RuntimeTemplateException('Could not render "' . $resource . '" on line ' . $lineNumber, 0, $exception);
            $exception->setResource($resource);

            throw $exception;
        }

        return $output;
    }

    /**
     * Extracts the resource and line number of the original template from the
     * provided exception through the /*# pointer comments
     * @param Exception $exception Occured exception
     * @param string $code Compiled template code
     * @param string $resource Name of the resolved resource by reference
     * @param string $lineNumber Line number in the resolved resource by
     * reference
     */
    private function extractResourceFromException(Exception $exception, $code, &$resource, &$lineNumber) {
        $trace = $exception->getTrace();
        $trace = array_shift($trace);

        $lines = explode("\n", $code);
        $line = $trace['line'] - 1;

        do {
            if ($lines[$line]) {
                $resourcePosition = mb_strpos($lines[$line], '/*#');
            } else {
                $resourcePosition = false;
            }

            if ($resourcePosition === false) {
                $line--;

                continue;
            }

            $linePosition = mb_strrpos($lines[$line], ':');
            $endPosition = mb_strrpos($lines[$line], '*/');

            $resource = mb_substr($lines[$line], $resourcePosition + 3, $linePosition - $resourcePosition - 3);
            $lineNumber = mb_substr($lines[$line], $linePosition + 1, $linePosition - $endPosition + 1);

            return;
        } while ($line >= 0);

        $resource = 'unknown';
        $lineNumber = 0;
    }

    /**
     * Gets the compiled template for the provided resource
     * @param string $resource Name of the template resource
     * @param TemplateContext $context Runtime context of the template
     * @param string $runtimeId Id for the compiled template function
     * @param string $extends Template code to append to the resource, used by a
     * dynamic extends block
     * @return string Compiled template
     */
    public function compile($resource, TemplateContext &$context = null, &$runtimeId = null, $extends = null) {
        // initialize context
        if (!$context) {
            $context = $this->context->createChild();
        }

        if ($runtimeId === null) {
            $runtimeId = $this->generateRuntimeId($resource);
        }

        $resourceHandler = $template = $context->getResourceHandler();

        if ($this->cache) {
            $resourceId = $this->generateResourceId($context, $resource, $extends);

            $cacheItem = $this->cache->get($resourceId);
            if ($cacheItem->isValid()) {
                // valid cache item
                $useCache = true;

                // check for changes in the templates
                if ($this->isDebug) {
                    $time = $cacheItem->getMeta('created');
                    $resources = explode(',', $cacheItem->getMeta('resources'));
                    foreach ($resources as $includedResource) {
                        if ($resourceHandler->getModificationTime($includedResource) > $time) {
                            // template has changed, skip the cache
                            $useCache = false;

                            break;
                        }
                    }
                }

                if ($useCache) {
                    // cache item is valid and no changes
                    $code = $cacheItem->getValue();
                    $runtimeId = $cacheItem->getMeta('runtime-id');

                    return $code;
                }
            }
        }

        // not cached, compile the template
        $template = $resourceHandler->getResource($resource);

        try {
            $code = $this->compileTemplate($context, $template, $runtimeId, $extends, $resource);
        } catch (CompileTemplateException $exception) {
            $message = null;
            $lineNumber = null;

            $previous = $exception;
            do {
                if ($previous instanceof CompileTemplateException) {
                    if ($previous->getResource()) {
                        $resource = $previous->getResource();
                        $lineNumber = $previous->getLineNumber();
                    }
                }

                $message = $previous->getMessage();
                $previous = $previous->getPrevious();
            } while ($previous instanceof CompileTemplateException);

            $exceptionMessage = 'Could not compile "' . $resource . '" on line ' . $lineNumber;

            $exception = new CompileTemplateException($exceptionMessage . ($message && $exceptionMessage !== $message ? ': ' . lcfirst($message) : ''));
            $exception->setResource($resource);
            $exception->setLineNumber($lineNumber);

            throw $exception;
        }

        $requestedResources = $resourceHandler->getRequestedResources();

        if ($this->cache) {
            // store the compiled code in the cache
            $cacheItem->setValue($code);
            $cacheItem->setMeta('runtime-id', $runtimeId);
            $cacheItem->setMeta('created', time());
            $cacheItem->setMeta('resources', implode(',', array_keys($requestedResources)));

            $this->cache->set($cacheItem);
        }

        return $code;
    }

    /**
     * Compiles the provided template
     * @param TemplateContext $context Runtime context of the template
     * @param string $template Template code which needs to be compiled
     * @param string $runtimeId Id for the compiled template function
     * @param string $extends Template code from a dynamic extends block
     * @param string $resource Name of the template resource
     * @return string Compiled template
     */
    private function compileTemplate(TemplateContext $context, $template, $runtimeId, $extends = null, $resource = null) {
        $context->preCompile();

        $this->compiler->setContext($context);

        $code = '';
        $code .= "\n";
        $code .= 'use huqis\\TemplateContext;';
        $code .= "\n\n";
        $code .= 'class huqisTemplate' . $runtimeId .' {' . "\n";
        $code .= "\n";
        $code .= '    public function render(TemplateContext $context) {' . "\n";
        $code .= $this->compiler->compile($template, $resource, 2, $extends);
        $code .= '    }' . "\n";
        $code .= "\n";
        $code .= '}';

        return $code;
    }

    /**
     * Executes the provided compiled code
     * @param TemplateContext $context Runtime context of the template
     * @param string $code Compiled template code
     * @param string $runtimeId Id of the compiled template function
     * @return string Rendered template
     */
    private function execute(TemplateContext $context, $code, $runtimeId) {
        ob_start();

        try {
            $this->executor->execute($context, $code, $runtimeId);
        } catch (Exception $exception) {
            ob_end_clean();

            throw $exception;
        }

        $output = ob_get_contents();

        ob_end_clean();

        return $output;
    }

    /**
     * Generates a resource id
     * @param TemplateContext $context Runtime context of the template
     * @param string $resource Name of the template resource
     * @param string $extends Template code from a dynamic extends block
     * @return string
     */
    private function generateResourceId(TemplateContext $context, $resource, $extends = null) {
        return mb_substr(crc32($resource . $this->compileId . '#' . $extends), 0, 10) . '-' . str_replace('/', '-', $resource);
    }

    /**
     * Generates a runtime id
     * @param string $resource Name of the template resource
     * @return string
     */
    private function generateRuntimeId($resource) {
        return StringHelper::generate() . sprintf('%u', crc32(microtime() . $resource . $this->compileId));
    }

}
