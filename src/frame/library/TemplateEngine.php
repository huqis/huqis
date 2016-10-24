<?php

namespace frame\library;

use frame\library\cache\TemplateCache;
use frame\library\exception\CompileTemplateException;
use frame\library\exception\RuntimeTemplateException;
use frame\library\helper\StringHelper;
use frame\library\resource\TemplateResourceHandler;

use \Exception;

/**
 * Main facade to the template engine
 */
class TemplateEngine {

    /**
     * Initial context for a render
     * @var \frame\library\TemplateContext
     */
    private $context;

    /**
     * Instance of the template compiler
     * @var \frame\library\TemplateCompiler
     */
    private $compiler;

    /**
     * Cache for the compiled templates
     * @var \frame\library\cache\TemplateCache
     */
    private $cache;

    /**
     * History of loaded code indexed by runtimeId
     * @var array
     */
    private $loadedCode;

    /**
     * Constructs a new template engine
     * @param \frame\library\TemplateContext $context Initial context for a
     * render
     * @param \frame\library\cache\TemplateCache $cache Instance of the cache
     * @return null
     */
    public function __construct(TemplateContext $context) {
        $this->context = $context;
        $this->context->setEngine($this);

        $this->compiler = new TemplateCompiler($this->context);
        $this->loadedCode = [];
    }

    /**
     * Gets the initial context for a render
     * @return \frame\library\TemplateContext
     */
    public function getContext() {
        return $this->context;
    }

    /**
     * Sets the cache for the compiled templates
     * @param \frame\library\cache\TemplateCache $cache Instance of the cache
     * @return null
     */
    public function setCache(TemplateCache $cache = null) {
        $this->cache = $cache;
    }

    /**
     * Renders a template resource
     * @param string $resource Name of the template resource
     * @param array $variables Variables assigned to the template
     * @param \frame\library\TemplateContext $context Initial context
     * @return string Rendered template
     * @param string $append Template code to append to the resource, used for a
     * dynamic extends block
     * @throws \frame\library\exception\TemplateException when a compile or
     * runtime error occured
     */
    public function render($resource, array $variables, TemplateContext $context = null, $append = null) {
        // initialize context
        if (!$context) {
            $context = $this->context->createChild();
        }

        if ($variables) {
            $context->setVariables($variables);
        }

        $runtimeId = $this->generateRuntimeId($resource);

        // retrieve and compile the template
        try {
            $code = $this->getResource($context, $resource, $runtimeId, $append);
        } catch (CompileTemplateException $exception) {
            $resource = $exception->getResource() ? $exception->getResource() : $resource;

            $suffix = null;
            $lineNumber = null;
            if ($exception instanceof CompileTemplateException && $exception->getLineNumber()) {
                $lineNumber = $exception->getLineNumber();
                if ($lineNumber) {
                    $suffix = ': syntax error on line ' . $lineNumber;
                }
            }

            $previous = $exception;
            do {
                $lastPrevious = $previous;
                $previous = $previous->getPrevious();
            } while ($previous instanceof CompileTemplateException);

            if ($previous === null) {
                $previous = $lastPrevious;
            }

            $exception = new CompileTemplateException('Could not compile ' . $resource . $suffix, 0, $previous);
            $exception->setResource($resource);
            $exception->setLineNumber($lineNumber);

            throw $exception;
        }

        // execute the compiled template with the initialized context
        try {
            $output = $this->execute($context, $code, $runtimeId);
        } catch (Exception $exception) {
            $exception = new RuntimeTemplateException('Could not render ' . $resource, 0, $exception);
            $exception->setResource($resource);

            throw $exception;
        }

        return $output;
    }

    /**
     * Gets the compiled template for the provided resource
     * @param TemplateContext $context Runtime context of the template
     * @param string $resource Name of the template resource
     * @param string $runtimeId Id for the compiled template function
     * @param string $append Template code to append to the resource, used for a
     * dynamic extends block
     * @return string Compiled template
     */
    private function getResource(TemplateContext $context, $resource, &$runtimeId, $append = null) {
        $resourceHandler = $template = $context->getResourceHandler();

        if ($this->cache) {
            $resourceId = $this->generateResourceId($context, $resource, $append);

            $cacheItem = $this->cache->get($resourceId);
            if ($cacheItem->isValid()) {
                // valid cache item, check for changes
                $useCache = true;

                if ($resourceHandler->getModificationTime($resource) > $cacheItem->getMeta('created')) {
                    // template has changed, skip the cache
                    $useCache = false;
                }

                if ($useCache) {
                    // cache item will be used
                    $code = $cacheItem->getValue();
                    $runtimeId = $cacheItem->getMeta('runtime-id');

                    return $code;
                }
            }
        }

        // not loaded before and not cached, compile the template
        $template = $resourceHandler->getResource($resource);
        $template .= $append;

        $code = $this->compile($context, $template, $runtimeId);

        if ($this->cache) {
            // cache the compiled code
            $cacheItem->setValue($code);
            $cacheItem->setMeta('runtime-id', $runtimeId);
            $cacheItem->setMeta('created', time());

            $this->cache->set($cacheItem);
        }

        return $code;
    }

    /**
     * Compiles the provided template
     * @param TemplateContext $context Runtime context of the template
     * @param string $template Template code which needs to be compiled
     * @param string $runtimeId Id for the compiled template function
     * @return string Compiled template
     */
    private function compile(TemplateContext $context, $template, $runtimeId) {
        $this->compiler->setContext($context);

        $code = '';
        $code .= 'use frame\\library\\TemplateContext;';
        $code .= 'function frameTemplate' . $runtimeId .'(TemplateContext $context) {';
        $code .= $this->compiler->compile($template);
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
            if (!isset($this->loadedCode[$runtimeId])) {
                $result = eval($code);
                if ($result === false) {
                    $error = error_get_last();

                    throw new RuntimeTemplateException($error['message'] . ' on line ' . $error['line']);
                }

                $this->loadedCode[$runtimeId] = true;
            }

            $template = 'frameTemplate' . $runtimeId;
            $template($context);
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
     * @return string
     */
    private function generateResourceId(TemplateContext $context, $resource, $append = null) {
        return substr(md5($resource . '#' . $append), 0, 10) . '-' . StringHelper::safeString($resource);
    }

    /**
     * Generates a runtime id
     * @return string
     */
    private function generateRuntimeId($resource) {
        return StringHelper::generate() . md5(microtime() . $resource);
    }

}
