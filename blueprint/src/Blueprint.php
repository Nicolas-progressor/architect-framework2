<?php

declare(strict_types=1);

namespace Blueprint\Engine;

use Blueprint\Engine\Exception\BlueprintException;
use Blueprint\Engine\Config\BlueprintConfig;
use Blueprint\Engine\Template\TemplateRenderer;
use Blueprint\Engine\Template\ElementRenderer;
use Blueprint\Engine\Template\DebugIntegration;
use Blueprint\Engine\Template\ExtensionLoader;
use Blueprint\Engine\Runtime\Runtime;
use Blueprint\Engine\Contracts\RuntimeInterface;

/**
 * Blueprint Template Engine
 * 
 * A Blade/Twig-like templating engine with automatic PHP code compilation,
 * template inheritance, filters, functions, and element/widget support.
 * 
 * Fully DI-based, no static methods or singletons.
 * 
 * @package Blueprint\Engine
 */
class Blueprint
{
    protected BlueprintConfig $config;
    protected Loader $loader;
    protected Compiler $compiler;
    protected RuntimeInterface $runtime;
    protected ?ElementManager $elementManager = null;
    
    protected TemplateRenderer $templateRenderer;
    protected ElementRenderer $elementRenderer;
    protected DebugIntegration $debugIntegration;
    
    protected array $context = [];
    protected ?object $container = null;
    protected ?string $currentTemplate = null;
    protected array $elementsConfig = [];

    /**
     * Constructor
     */
    public function __construct(
        array|BlueprintConfig $config, 
        ?object $container = null,
        ?RuntimeInterface $runtime = null
    ) {
        $this->config = is_array($config) 
            ? new BlueprintConfig($config) 
            : $config;
        $this->container = $container;
        
        $this->loader = new Loader($this->config);
        $this->compiler = new Compiler();
        $this->runtime = $runtime ?? RuntimeFactory::create();
        
        // Initialize components
        $this->initializeComponents();
        
        // Load extensions
        $extensionLoader = new ExtensionLoader();
        $extensionLoader->loadExtensions($this);
    }

    /**
     * Initialize components
     */
    protected function initializeComponents(): void
    {
        $this->templateRenderer = new TemplateRenderer(
            $this->loader,
            $this->compiler,
            $this->config->isDebug(),
            $this->config->showErrors()
        );
        
        $this->elementRenderer = new ElementRenderer(
            $this->loader,
            $this->container,
            $this->context,
            $this->config->isDebug()
        );
        
        $this->debugIntegration = new DebugIntegration($this->container);
    }

    // ============ Configuration ============

    /**
     * Get configuration
     */
    public function getConfig(): BlueprintConfig
    {
        return $this->config;
    }

    /**
     * Set DI container
     */
    public function setContainer(?object $container): self
    {
        $this->container = $container;
        $this->elementRenderer->setContainer($container);
        $this->debugIntegration->setContainer($container);
        return $this;
    }

    /**
     * Get DI container
     */
    public function getContainer(): ?object
    {
        return $this->container;
    }

    /**
     * Get runtime
     */
    public function getRuntime(): RuntimeInterface
    {
        return $this->runtime;
    }

    /**
     * Check if debug mode is enabled
     */
    public function isDebug(): bool
    {
        return $this->config->isDebug();
    }

    // ============ Template Paths ============

    /**
     * Add template path
     */
    public function addPath(string $path): self
    {
        $this->loader->addPath($path);
        return $this;
    }

    /**
     * Set template paths
     */
    public function setPaths(array $paths): self
    {
        $this->loader->setPaths($paths);
        return $this;
    }

    /**
     * Add path for specific application
     */
    public function addAppPath(string $app, string $templateDir = 'app/{app}/template/'): self
    {
        $this->loader->addAppPath($app, $templateDir);
        return $this;
    }

    /**
     * Add template extension
     */
    public function addExtension(string $extension): self
    {
        $this->loader->addExtension($extension);
        return $this;
    }

    // ============ Compilation ============

    /**
     * Compile template to PHP code
     */
    public function compile(string $template, ?string $name = null): string
    {
        return $this->templateRenderer->compile($template);
    }

    /**
     * Compile string to PHP code
     */
    public function compileString(string $source, string $name = 'string'): string
    {
        return $this->templateRenderer->compileString($source, $name);
    }

    // ============ Rendering ============

    /**
     * Render template with given context
     */
    public function render(string $template, array $context = []): string
    {
        $fullContext = array_merge($this->context, $context);
        $fullContext['__runtime'] = $this->runtime;
        $fullContext['__blueprint'] = $this;
        
        $this->loadElementsConfig($template);
        
        $cacheFresh = $this->loader->isCacheEnabled() && $this->loader->isFresh($template);
        
        $this->debugIntegration->logCompile(
            $template,
            $this->loader->getCompiledPath($template),
            $cacheFresh
        );

        return $this->templateRenderer->render($template, $fullContext, $this);
    }

    /**
     * Render string template
     */
    public function renderString(string $source, array $context = []): string
    {
        $fullContext = array_merge($this->context, $context);
        $fullContext['__runtime'] = $this->runtime;
        $fullContext['__blueprint'] = $this;
        return $this->templateRenderer->renderString($source, $fullContext, $this);
    }

    /**
     * Load template source (for extends/include)
     */
    public function loadTemplate(string $name): ?string
    {
        return $this->loader->getSource($name);
    }

    /**
     * Check if template exists
     */
    public function exists(string $template): bool
    {
        return $this->loader->exists($template);
    }

    // ============ Global Context ============

    /**
     * Add global context variable
     */
    public function addGlobal(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Add multiple global variables
     */
    public function addGlobals(array $globals): self
    {
        $this->context = array_merge($this->context, $globals);
        return $this;
    }

    /**
     * Get global context
     */
    public function getGlobals(): array
    {
        return $this->context;
    }

    /**
     * Clear global context
     */
    public function clearGlobals(): self
    {
        $this->context = [];
        return $this;
    }

    // ============ Elements/Widgets ============

    /**
     * Get Element Manager
     */
    public function getElementManager(): ElementManager
    {
        if ($this->elementManager === null) {
            $this->elementManager = new ElementManager($this);
            
            foreach ($this->config->getElementsDirs() as $dir) {
                $this->elementManager->addDirectory($dir);
            }
            
            foreach ($this->loader->getPaths() as $path) {
                $this->elementManager->addDirectory($path . '/elements');
            }
            
            $this->elementRenderer->setElementManager($this->elementManager);
        }

        return $this->elementManager;
    }

    /**
     * Register element callback
     */
    public function registerElement(string $name, callable $callback): self
    {
        $this->getElementManager()->register($name, $callback);
        return $this;
    }

    /**
     * Register element class
     */
    public function registerElementClass(string $name, string $className): self
    {
        $this->getElementManager()->registerClass($name, $className);
        return $this;
    }

    /**
     * Register element instance
     */
    public function registerElementInstance(ElementInterface $element): self
    {
        $this->getElementManager()->registerInstance($element);
        return $this;
    }

    /**
     * Add elements directory
     */
    public function addElementsDirectory(string $directory): self
    {
        $this->getElementManager()->addDirectory($directory);
        return $this;
    }

    /**
     * Render element (widget)
     */
    public function element(string $name, array $data = []): string
    {
        $this->elementRenderer->setContext($this->context);
        $this->elementRenderer->setCurrentTemplate($this->currentTemplate);
        $this->elementRenderer->setElementsConfig($this->elementsConfig);
        
        return $this->elementRenderer->render($name, $data, $this);
    }

    /**
     * Render widget (alias for element)
     */
    public function widget(string $name, array $data = []): string
    {
        return $this->element($name, $data);
    }

    // ============ Extensions & Functions ============

    /**
     * Register extension
     */
    public function registerExtension(object $extension): self
    {
        // Extensions are stored for reference
        return $this;
    }

    /**
     * Register function for use in templates
     */
    public function registerFunction(string $name, callable $function): self
    {
        $this->runtime->registerFunction($name, $function);
        return $this;
    }

    /**
     * Register filter for use in templates
     */
    public function registerFilter(string $name, callable $filter): self
    {
        $this->runtime->registerFilter($name, $filter);
        return $this;
    }

    // ============ Debug ============

    /**
     * Log Blueprint error to Debug service
     */
    public function logError(string $template, string $message, ?string $compiledCode = null): void
    {
        $this->debugIntegration->logError($template, $message, $compiledCode);
    }

    /**
     * Initialize Blueprint data in Debug panel
     */
    public function initDebugData(): void
    {
        $this->debugIntegration->initData(
            $this->loader->getPaths(),
            $this->loader->isCacheEnabled(),
            $this->loader->getCachePath()
        );
    }

    // ============ Cache ============

    /**
     * Clear all cache
     */
    public function clearCache(): bool
    {
        return $this->loader->clearCache();
    }

    /**
     * Clear cache for specific template
     */
    public function clearCacheFor(string $template): bool
    {
        return $this->loader->clearCacheFor($template);
    }

    // ============ Getters ============

    /**
     * Get loader instance
     */
    public function getLoader(): Loader
    {
        return $this->loader;
    }

    /**
     * Get elements configuration
     */
    public function getElementsConfig(): array
    {
        return $this->elementsConfig;
    }

    /**
     * Get compiler instance
     */
    public function getCompiler(): Compiler
    {
        return $this->compiler;
    }

    /**
     * Get list of available templates
     */
    public function getTemplateList(): array
    {
        return $this->loader->getTemplateList();
    }

    // ============ Template State ============

    /**
     * Set current template (for loading elements.json)
     */
    public function setCurrentTemplate(?string $template): self
    {
        if (empty($this->elementsConfig) || $this->currentTemplate !== $template) {
            $this->currentTemplate = $template;
            $this->loadElementsConfig($template);
        }
        return $this;
    }

    /**
     * Force reload elementsConfig
     */
    public function reloadElementsConfig(): void
    {
        $this->elementsConfig = [];
        if ($this->currentTemplate) {
            $this->loadElementsConfig($this->currentTemplate);
        }
    }

    /**
     * Set debug mode
     */
    public function setDebug(bool $debug): self
    {
        $this->config->set('debug', $debug);
        $this->templateRenderer->setDebug($debug);
        $this->elementRenderer->setDebug($debug);
        return $this;
    }

    /**
     * Output debug information
     */
    public function dump(mixed $var): string
    {
        ob_start();
        var_dump($var);
        return ob_get_clean();
    }

    // ============ Elements Configuration Loading ============

    /**
     * Load elements configuration for template
     */
    protected function loadElementsConfig(?string $template): void
    {
        if (count($this->elementsConfig) > 0) {
            return;
        }
        
        if (!$template || !$this->container) {
            return;
        }
        
        $templatePath = $this->loader->findTemplate($template);
        
        if (!$templatePath) {
            return;
        }
        
        $templateDir = dirname($templatePath);
        
        // Load global elements.json
        $elementsFile = $templateDir . '/elements.json';
        
        if (file_exists($elementsFile)) {
            $data = json_decode(file_get_contents($elementsFile), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->elementsConfig = $data;
            }
        }
        
        // Load routed elements (elements/*.json)
        $elementsDir = $templateDir . '/elements';
        if (is_dir($elementsDir)) {
            $files = glob($elementsDir . '/*.json');
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->elementsConfig = array_merge($this->elementsConfig, $data);
                }
            }
        }
        
        $this->loadRoutedElements($templateDir);
    }

    /**
     * Load routed elements for current route
     */
    protected function loadRoutedElements(string $templateDir): void
    {
        if (!$this->container) {
            return;
        }

        try {
            $router = $this->container->get('router');
            $module = $router->getModule();
            $controller = $router->getController();
            $action = $router->getAction();
        } catch (\Exception $e) {
            return;
        }
        
        $elementsDir = $templateDir . '/elements';
        if (!is_dir($elementsDir)) {
            return;
        }
        
        $files = glob($elementsDir . '/*.json');
        foreach ($files as $file) {
            $routedData = json_decode(file_get_contents($file), true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($routedData)) {
                continue;
            }
            
            if (isset($routedData[$module][$controller][$action])) {
                $elements = $routedData[$module][$controller][$action];
                if (is_array($elements)) {
                    $this->elementsConfig = array_merge($this->elementsConfig, $elements);
                }
            }
        }
    }
}

