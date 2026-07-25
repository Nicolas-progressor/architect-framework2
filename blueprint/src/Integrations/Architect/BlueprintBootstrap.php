<?php

declare(strict_types=1);

namespace Blueprint\Engine\Integrations\Architect;

use Blueprint\Engine\Blueprint;
use Blueprint\Engine\RuntimeFactory;

/**
 * Blueprint Bootstrap for Architect Framework
 * 
 * Integrates Blueprint template engine with Architect's EnvironmentManager,
 * loading configuration from app/config/blueprint.json and environment-specific configurations.
 * 
 * No static methods, no singletons - pure DI.
 * 
 * @package Blueprint\Engine\Integrations\Architect
 */
class BlueprintBootstrap
{
    protected ?object $container = null;
    protected ?object $environment = null;
    
    /**
     * Constructor with DI
     * 
     * @param object|null $container DI container
     * @param object|null $environment Environment manager
     */
    public function __construct(?object $container = null, ?object $environment = null)
    {
        $this->container = $container;
        $this->environment = $environment;
    }
    
    /**
     * Bootstrap Blueprint with configuration from Architect
     */
    public function bootstrap(): void
    {
        $config = $this->loadConfig();
        
        if (empty($config)) {
            $config = $this->getDefaultConfig();
        }
        
        // Apply environment variables
        $config = $this->applyEnvironmentVariables($config);
        
        // Register in container
        if ($this->container !== null) {
            $this->registerInContainer($config);
            $this->setupDebugIntegration();
        }
    }
    
    /**
     * Set container
     */
    public function setContainer(object $container): self
    {
        $this->container = $container;
        return $this;
    }
    
    /**
     * Set environment manager
     */
    public function setEnvironment(object $environment): self
    {
        $this->environment = $environment;
        return $this;
    }
    
    /**
     * Load configuration
     */
    protected function loadConfig(): array
    {
        // Try environment first
        if ($this->environment !== null && method_exists($this->environment, 'get')) {
            $envConfig = $this->environment->get('blueprint');
            if (is_array($envConfig) && !empty($envConfig)) {
                return $envConfig;
            }
        }
        
        // Try file
        return $this->loadBlueprintConfig();
    }
    
    /**
     * Load base configuration from app/config/blueprint.json
     */
    protected function loadBlueprintConfig(): array
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 5);
        
        $configPaths = [
            $root . '/app/config/blueprint.json',
            $root . '/config/blueprint.json',
        ];
        
        foreach ($configPaths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $config = json_decode($content, true);
                
                if (json_last_error() === JSON_ERROR_NONE && !empty($config)) {
                    return $config;
                }
            }
        }
        
        return [];
    }
    
    /**
     * Get default configuration
     */
    protected function getDefaultConfig(): array
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 5);
        
        return [
            'debug' => false,
            'autoescape' => true,
            'cache' => $root . '/cache/blueprints/',
            'cache_enabled' => false,
            'paths' => $this->getDefaultPaths(),
            'extensions' => ['.blu', '.twig', '.html'],
            'strict_variables' => false,
        ];
    }
    
    /**
     * Get default template paths
     */
    protected function getDefaultPaths(): array
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 5);
        
        return [
            $root . '/app/apps/{app}/template/',
            $root . '/app/template/',
        ];
    }
    
    /**
     * Apply environment variables to configuration
     */
    protected function applyEnvironmentVariables(array $config): array
    {
        $envDebug = getenv('BLUEPRINT_DEBUG');
        if ($envDebug !== false) {
            $config['debug'] = (bool) $envDebug;
        }
        
        $envCache = getenv('BLUEPRINT_CACHE');
        if ($envCache !== false) {
            $config['cache_enabled'] = (bool) $envCache;
        }
        
        return $config;
    }
    
    /**
     * Register Blueprint in DI container
     */
    protected function registerInContainer(array $config): void
    {
        if ($this->container === null) {
            return;
        }
        
        $provider = new BlueprintServiceProvider();
        $provider->register($this->container, $config);
    }
    
    /**
     * Setup debug integration
     */
    protected function setupDebugIntegration(): void
    {
        if ($this->container === null) {
            return;
        }
        
        try {
            if (!method_exists($this->container, 'has') || !$this->container->has('debug')) {
                return;
            }
            
            $debug = $this->container->get('debug');
            if (!method_exists($debug, 'isEnabled') || !$debug->isEnabled()) {
                return;
            }
            
            $blueprint = $this->container->get('blueprint');
            $blueprint->initDebugData();
        } catch (\Throwable $e) {
            // Debug is not critical, ignore errors
        }
    }
    
    /**
     * Add paths for specific application context
     */
    public function addAppContextPaths(Blueprint $blueprint, string $appDir, ?string $templateName = null): void
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 5);
        
        $paths = [];
        
        // Template-specific layouts
        if ($templateName) {
            $templateLayouts = $appDir . 'template/' . $templateName . '/layouts/';
            if (is_dir($templateLayouts)) {
                $paths[] = $templateLayouts;
            }
            
            $templateRoot = $appDir . 'template/' . $templateName . '/';
            if (is_dir($templateRoot)) {
                $paths[] = $templateRoot;
            }
        }
        
        // App layouts
        $appLayouts = $appDir . 'template/layouts/';
        if (is_dir($appLayouts)) {
            $paths[] = $appLayouts;
        }
        
        // App template root
        $appTemplate = $appDir . 'template/';
        if (is_dir($appTemplate)) {
            $paths[] = $appTemplate;
        }
        
        // Global layouts
        $globalLayouts = $root . '/app/template/layouts/';
        if (is_dir($globalLayouts)) {
            $paths[] = $globalLayouts;
        }
        
        // Global template root
        $globalTemplate = $root . '/app/template/';
        if (is_dir($globalTemplate)) {
            $paths[] = $globalTemplate;
        }
        
        $loader = $blueprint->getLoader();
        foreach ($paths as $path) {
            $loader->addPath($path);
        }
    }
    
    /**
     * Add element paths for specific application context
     */
    public function addElementPaths(Blueprint $blueprint, string $appDir, ?string $templateName = null, ?string $modulePath = null): void
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 5);
        
        $paths = [];
        
        // Template-specific elements
        if ($templateName) {
            $templateElements = $appDir . 'template/' . $templateName . '/elements/';
            if (is_dir($templateElements)) {
                $paths[] = $templateElements;
            }
        }
        
        // App elements
        $appElements = $appDir . 'template/elements/';
        if (is_dir($appElements)) {
            $paths[] = $appElements;
        }
        
        // Module view elements
        if ($modulePath) {
            $moduleElements = rtrim($modulePath, '/') . '/elements/';
            if (is_dir($moduleElements)) {
                $paths[] = $moduleElements;
            }
        }
        
        // Global elements
        $globalElements = $root . '/app/template/elements/';
        if (is_dir($globalElements)) {
            $paths[] = $globalElements;
        }
        
        $loader = $blueprint->getLoader();
        foreach ($paths as $path) {
            $loader->addPath($path);
        }
        
        $elementManager = $blueprint->getElementManager();
        foreach ($paths as $path) {
            $elementManager->addDirectory($path);
        }
    }
}
