<?php

declare(strict_types=1);

namespace Architect\Services\Blueprint\Elements;

use Architect\Services\Blueprint\Contracts\ElementRendererInterface;
use Architect\Services\Blueprint\Contracts\ContextManagerInterface;
use Architect\Services\Blueprint\Contracts\BlueprintConfigInterface;

/**
 * Main element renderer combining MVC and template elements
 */
final class ElementRenderer implements ElementRendererInterface
{
    private ElementConfigLoader $configLoader;
    private MvcElementRenderer $mvcRenderer;
    private RoutedElementResolver $routedResolver;
    private ContextManagerInterface $contextManager;
    private BlueprintConfigInterface $config;
    
    private array $elementsConfig = [];

    public function __construct(
        ElementConfigLoader $configLoader,
        MvcElementRenderer $mvcRenderer,
        RoutedElementResolver $routedResolver,
        ContextManagerInterface $contextManager,
        BlueprintConfigInterface $config
    ) {
        $this->configLoader = $configLoader;
        $this->mvcRenderer = $mvcRenderer;
        $this->routedResolver = $routedResolver;
        $this->contextManager = $contextManager;
        $this->config = $config;
        
        $this->loadConfiguration();
    }

    public function render(string $name, array $data = []): string
    {
        // Check MVC element first
        $element = $this->elementsConfig[$name] ?? null;
        
        if ($element) {
            return $this->mvcRenderer->render($element, $data);
        }
        
        // Check routed element
        $routedElement = $this->routedResolver->resolve($name);
        
        if ($routedElement) {
            return $this->mvcRenderer->render($routedElement, $data);
        }
        
        return '';
    }

    public function exists(string $name): bool
    {
        return isset($this->elementsConfig[$name])
            || $this->routedResolver->resolve($name) !== null;
    }

    public function reload(): void
    {
        $this->elementsConfig = [];
        $this->routedResolver->setRoutedElements([]);
        $this->loadConfiguration();
    }

    /**
     * Load configuration from current context
     */
    private function loadConfiguration(): void
    {
        $appDir = $this->contextManager->getCurrentAppDir();
        $template = $this->contextManager->getCurrentTemplate();
        
        if (!$appDir) {
            return;
        }
        
        // Load elements config
        $this->elementsConfig = $this->configLoader->loadForTemplate($appDir, $template);
        
        // Load routed elements
        $routedElements = $this->configLoader->loadRoutedElements($appDir, $template);
        $this->routedResolver->setRoutedElements($routedElements);
    }
}
    
