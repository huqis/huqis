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
    public function __construct(TemplateContext $context, TemplateCache $cache = null) {
        $this->context = $context;
        $this->context->setEngine($this);

        $this->setCache($cache);

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
     * @param string $extends Template code from a dynamic extends block, used
     * by the compiler
     * @return string Rendered template
     * @throws \frame\library\exception\TemplateException when a compile or
     * runtime error occured
     */
    public function render($resource, array $variables, TemplateContext $context = null, $extends = null) {
        // initialize context
        if (!$context) {
            $context = $this->context->createChild();
        }

        if ($variables) {
            $context->setVariables($variables, false);
        }

        $runtimeId = $this->generateRuntimeId($resource);

        // retrieve and compile the template
        try {
            $code = $this->getResource($context, $resource, $runtimeId, $extends);
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
     * @param string $extends Template code to append to the resource, used by a
     * dynamic extends block
     * @return string Compiled template
     */
    private function getResource(TemplateContext $context, $resource, &$runtimeId, $extends = null) {
        $resourceHandler = $template = $context->getResourceHandler();

        if ($this->cache) {
            $resourceId = $this->generateResourceId($context, $resource, $extends);

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

        $code = $this->compile($context, $template, $runtimeId, $extends);

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
     * @param string $extends Template code from a dynamic extends block
     * @return string Compiled template
     */
    private function compile(TemplateContext $context, $template, $runtimeId, $extends = null) {
        $context->preCompile();

        $this->compiler->setContext($context);

        $code = '';
        $code .= "\n";
        $code .= 'use frame\\library\\TemplateContext;';
        $code .= "\n\n";
        $code .= 'function frameTemplate' . $runtimeId .'(TemplateContext $context) {' . "\n";
        $code .= $this->compiler->compile($template, $extends);
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
                $file = tempnam(sys_get_temp_dir(), 'frame-' . $runtimeId . '-');
                file_put_contents($file, '<?php ' . $code);
                include($file);

                // $result = eval($code);
                // if ($result === false) {
                    // $error = error_get_last();

                    // throw new RuntimeTemplateException($error['message'] . ' on line ' . $error['line']);
                // }

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
     * @param TemplateContext $context Runtime context of the template
     * @param string $resource Name of the template resource
     * @param string $extends Template code from a dynamic extends block
     * @return string
     */
    private function generateResourceId(TemplateContext $context, $resource, $extends = null) {
        return substr(crc32($resource . '#' . $extends), 0, 10) . '-' . str_replace('/', '-', $resource);
    }

    /**
     * Generates a runtime id
     * @param string $resource Name of the template resource
     * @return string
     */
    private function generateRuntimeId($resource) {
        return StringHelper::generate() . crc32(microtime() . $resource);
    }

}
