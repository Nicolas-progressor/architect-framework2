<?php

declare(strict_types=1);

namespace Blueprint\Engine\Template;

use Blueprint\Engine\ElementManager;
use Blueprint\Engine\Loader;

/**
 * Element Renderer
 * 
 * Handles rendering of elements and widgets.
 * Supports MVC widgets, template files, and registered callbacks.
 * 
 * @package Blueprint\Engine\Template
 */
class ElementRenderer
{
    protected Loader $loader;
    protected ?ElementManager $elementManager = null;
    protected ?object $container = null;
    protected array $elementsConfig = [];
    protected array $context = [];
    protected ?string $currentTemplate = null;
    protected bool $debug = false;

    public function __construct(
        Loader $loader,
        ?object $container = null,
        array $context = [],
        bool $debug = false
    ) {
        $this->loader = $loader;
        $this->container = $container;
        $this->context = $context;
        $this->debug = $debug;
    }

    /**
     * Set container
     */
    public function setContainer(?object $container): self
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Set element manager
     */
    public function setElementManager(ElementManager $manager): self
    {
        $this->elementManager = $manager;
        return $this;
    }

    /**
     * Get or create element manager
     */
    public function getElementManager(): ElementManager
    {
        return $this->elementManager;
    }

    /**
     * Set elements configuration
     */
    public function setElementsConfig(array $config): self
    {
        $this->elementsConfig = $config;
        return $this;
    }

    /**
     * Get elements configuration
     */
    public function getElementsConfig(): array
    {
        return $this->elementsConfig;
    }

    /**
     * Set current template
     */
    public function setCurrentTemplate(?string $template): self
    {
        $this->currentTemplate = $template;
        return $this;
    }

    /**
     * Set context
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Set debug mode
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * Render element (widget)
     * 
     * Elements can be:
     * 1. MVC widgets (with container + elements.json)
     * 2. Template files (.blu in elements/)
     * 3. Registered callbacks/classes (via ElementManager)
     */
    public function render(string $name, array $data = [], ?object $blueprint = null): string
    {
        // 1. If Container and element config exists - try to render MVC widget
        if ($this->container && isset($this->elementsConfig[$name])) {
            $elementConfig = $this->elementsConfig[$name];
            
            try {
                $result = $this->renderMvcWidget($elementConfig, $data);
                
                if ($result !== '' && !str_contains($result, 'not found')) {
                    return $result;
                }
            } catch (\Throwable $e) {
                // If widget rendering fails - continue with fallback
            }
        }

        // 2. Try ElementManager (registered callbacks, classes, template files)
        if ($this->elementManager && $this->elementManager->has($name)) {
            return $this->elementManager->render($name, array_merge($this->context, $data));
        }
        
        // 3. Fallback: try to load .blu file element relative to current template
        if ($this->currentTemplate && $blueprint) {
            $elementTemplate = dirname($this->currentTemplate) . '/elements/' . $name;
            if ($this->loader->exists($elementTemplate)) {
                return $blueprint->render($elementTemplate, array_merge($this->context, $data));
            }
        }
        
        // 4. Try elements/ in template paths
        if ($blueprint) {
            $elementTemplate = 'elements/' . $name;
            if ($this->loader->exists($elementTemplate)) {
                return $blueprint->render($elementTemplate, array_merge($this->context, $data));
            }
        }

        // Element not found
        if ($this->debug) {
            return "<!-- Element '{$name}' not found -->";
        }
        
        return '';
    }

    /**
     * Render widget via MVC
     */
    protected function renderMvcWidget(array $config, array $data = []): string
    {
        if (!isset($config['module']) || !isset($config['controller']) || !isset($config['action'])) {
            return "<!-- Widget config invalid: missing module/controller/action -->";
        }

        $apps = $this->container->get('apps');
        $module = $config['module'];
        $controller = $config['controller'];
        $action = $config['action'];

        // Priority for template elements: app modules -> global modules
        $appPath = $apps->appdir . "modules/{$module}/widget/{$controller}.php";
        $globalPath = APP_DIR . "modules/{$module}/widget/{$controller}.php";
        $isGlobal = false;
        
        if (file_exists($appPath)) {
            require_once $appPath;
            $namespace = "app\\{$apps->app}\\modules\\{$module}\\widget";
            $className = "\\{$namespace}\\" . ucfirst($controller);
        } elseif (file_exists($globalPath)) {
            require_once $globalPath;
            $namespace = "{$module}\\widget";
            $className = "\\{$namespace}\\" . ucfirst($controller);
            $isGlobal = true;
        } else {
            return "<!-- Widget '{$module}/{$controller}' not found -->";
        }
        
        if (!class_exists($className)) {
            return "<!-- Widget class '{$className}' not found -->";
        }

        try {
            $widget = new $className($this->container, $module, $isGlobal);
            
            $dataMethod = "{$action}_app_data";
            $outputMethod = "{$action}_app_output";
            
            if (method_exists($widget, $dataMethod)) {
                $widget->{$dataMethod}();
            }
            
            ob_start();
            if (method_exists($widget, $outputMethod)) {
                $widget->{$outputMethod}();
            }
            return ob_get_clean();
            
        } catch (\Throwable $e) {
            if ($this->debug) {
                return "<!-- Widget error: {$e->getMessage()} -->";
            }
            return '';
        }
    }
}
