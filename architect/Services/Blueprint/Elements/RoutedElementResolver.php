<?php

declare(strict_types=1);

namespace Architect\Services\Blueprint\Elements;

use Architect\Core\Container;

/**
 * Resolves elements based on current route
 */
final class RoutedElementResolver
{
    private Container $container;
    private array $routedElements = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Set routed elements configuration
     */
    public function setRoutedElements(array $elements): void
    {
        $this->routedElements = $elements;
    }

    /**
     * Get element configuration for current route
     */
    public function resolve(string $name): ?array
    {
        if (empty($this->routedElements)) {
            return null;
        }

        $route = $this->getCurrentRoute();
        
        return $this->findElement($name, $route);
    }

    /**
     * Get current route info
     */
    private function getCurrentRoute(): array
    {
        if (!$this->container->has('router')) {
            return ['module' => '', 'controller' => '', 'action' => ''];
        }

        $router = $this->container->get('router');
        
        return [
            'module' => method_exists($router, 'getModule') ? $router->getModule() : '',
            'controller' => method_exists($router, 'getController') ? $router->getController() : '',
            'action' => method_exists($router, 'getAction') ? $router->getAction() : '',
        ];
    }

    /**
     * Find element in nested route structure
     * Supports: module -> controller -> action -> element
     *           module -> controller (all actions) -> element
     *           action -> element (backward compatibility)
     */
    private function findElement(string $name, array $route): ?array
    {
        $module = $route['module'];
        $controller = $route['controller'];
        $action = $route['action'];
        
        // 1. Full path: module -> controller -> action -> element
        if ($module && isset($this->routedElements[$module])) {
            $moduleData = $this->routedElements[$module];
            
            if ($controller && isset($moduleData[$controller])) {
                $controllerData = $moduleData[$controller];
                
                // Exact action match
                if ($action && isset($controllerData[$action]) && is_array($controllerData[$action])) {
                    return $controllerData[$action][$name] ?? null;
                }
                
                // Controller level (all actions) - check if controllerData has element directly
                if (isset($controllerData[$name]) && is_array($controllerData)) {
                    return $controllerData[$name];
                }
            }
        }
        
        // 2. Backward compatibility: action -> element
        if ($action && isset($this->routedElements[$action])) {
            $actionData = $this->routedElements[$action];
            if (isset($actionData[$name]) && is_array($actionData)) {
                return $actionData[$name];
            }
        }
        
        return null;
    }
}
