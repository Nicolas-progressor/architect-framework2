<?php

declare(strict_types=1);

namespace Architect\Services\Blueprint;

use Blueprint\Engine\Blueprint;
use Architect\Core\Container;
use Architect\Contracts\ServiceInterface;
use Architect\Services\Blueprint\Contracts\BlueprintConfigInterface;
use Architect\Services\Blueprint\Contracts\ContextManagerInterface;
use Architect\Services\Blueprint\Contracts\ElementRendererInterface;
use RuntimeException;

/**
 * Blueprint Service for Architect Framework
 * 
 * Provides template rendering through Blueprint engine with DI architecture
 */
final class BlueprintService implements ServiceInterface
{
    private Container $container;
    private ?BlueprintConfigInterface $config = null;
    private ?ContextManagerInterface $contextManager = null;
    private ?ElementRendererInterface $elementRenderer = null;
    private ?Blueprint $blueprint = null;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Initialize service with configuration
     */
    public function initialize(BlueprintConfigInterface $config, ContextManagerInterface $contextManager): void
    {
        $this->config = $config;
        $this->contextManager = $contextManager;
        $this->blueprint = $this->createBlueprint();
    }
    
    /**
     * Set element renderer
     */
    public function setElementRenderer(ElementRendererInterface $renderer): void
    {
        $this->elementRenderer = $renderer;
    }
    
    public function boot(): void
    {
        // Service is initialized through ServiceProvider
    }

    /**
     * Get underlying Blueprint instance
     */
    public function getBlueprint(): Blueprint
    {
        return $this->blueprint ?? throw new RuntimeException('Blueprint not initialized');
    }
    
    /**
     * Get configuration
     */
    public function getConfig(): BlueprintConfigInterface
    {
        return $this->config ?? throw new RuntimeException('Blueprint config not initialized');
    }
    
    /**
     * Get context manager
     */
    public function getContextManager(): ContextManagerInterface
    {
        return $this->contextManager ?? throw new RuntimeException('Context manager not initialized');
    }
    
    /**
     * Set application context for template resolution
     */
    public function setContext(string $appDir, ?string $templateName = null): void
    {
        $this->contextManager?->setContext($appDir, $templateName);
        $this->syncPathsToBlueprint();
        
        // Reload element renderer after context change
        if ($this->elementRenderer) {
            $this->elementRenderer->reload();
        }
    }
            
    /**
     * Set module context for view resolution
     */
    public function setModuleContext(string $modulePath): void
    {
        $this->contextManager?->setModuleContext($modulePath);
        $this->syncPathsToBlueprint();
    }
    
    /**
     * Render template
     */
    public function render(string $template, array $data = []): string
    {
        return $this->getBlueprint()->render($template, $data);
    }
    
    /**
     * Render string
     */
    public function renderString(string $source, array $data = []): string
    {
        return $this->getBlueprint()->renderString($source, $data);
    }
    
    /**
     * Check if template exists
     */
    public function exists(string $template): bool
    {
        return $this->getBlueprint()->exists($template);
    }
    
    /**
     * Add global variable
     */
    public function addGlobal(string $key, mixed $value): void
    {
        $this->getBlueprint()->addGlobal($key, $value);
    }
    
    /**
     * Add multiple global variables
     */
    public function addGlobals(array $globals): void
    {
        $this->getBlueprint()->addGlobals($globals);
    }
    
    /**
     * Register filter
     */
    public function registerFilter(string $name, callable $filter): void
    {
        $this->getBlueprint()->registerFilter($name, $filter);
    }
    
    /**
     * Register function
     */
    public function registerFunction(string $name, callable $function): void
    {
        $this->getBlueprint()->registerFunction($name, $function);
    }
    
    /**
     * Clear template cache
     */
    public function clearCache(): bool
    {
        return $this->getBlueprint()->clearCache();
    }

    /**
     * Initialize debug data in Debug panel
     */
    public function initDebugData(): void
    {
        $this->getBlueprint()->initDebugData();
    }

    /**
     * Get current app directory
     */
    public function getCurrentApp(): ?string
    {
        return $this->contextManager?->getCurrentAppDir();
    }

    /**
     * Get current template name
     */
    public function getCurrentTemplate(): ?string
    {
        return $this->contextManager?->getCurrentTemplate();
    }

    /**
     * Create Blueprint instance
     */
    private function createBlueprint(): Blueprint
    {
        $configArray = $this->config->all();
        
        $blueprint = new Blueprint($configArray, $this->container);
        
        // Set initial paths
        $this->syncPathsToBlueprint();
        
        return $blueprint;
    }

    /**
     * Sync paths from context manager to Blueprint
     */
    private function syncPathsToBlueprint(): void
    {
        if (!$this->blueprint || !$this->contextManager) {
            return;
        }
        
        $paths = $this->contextManager->getPaths();
        
        // Clear existing paths
        if (method_exists($this->blueprint, 'setPaths')) {
            $this->blueprint->setPaths([]);
        }
        
        // Add new paths
        foreach ($paths as $path) {
            if (method_exists($this->blueprint, 'addPath')) {
                $this->blueprint->addPath($path);
            }
        }
    }
}
