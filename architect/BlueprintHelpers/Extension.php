<?php

/**
 * Blueprint Helpers Extension
 * 
 * Integrates Helpers system with Blueprint templates.
 * Provides dynamic access to Helpers via helpers() function and shorthand aliases.
 * 
 * @package     Architect\BlueprintHelpers
 * @author      Architect Team <team@architect.dev>
 * @license     MIT
 */

declare(strict_types=1);

namespace Architect\BlueprintHelpers;

use Blueprint\Engine\Blueprint;
use Blueprint\Engine\BlueprintExtension;
use Architect\Helpers\Core\Facade;
use Architect\Helpers\Core\HelperDiscovery;
use Architect\Helpers\Core\Contracts\HelperInterface;

/**
 * Blueprint extension for Helpers integration.
 * 
 * Usage in templates:
 *   {{ helpers('Html').icon('house') }}
 *   {{ html().icon('house') }}
 *   {{ breadcrumbs().render() }}
 *   {{ arr().wrap(['a', 'b']) }}
 */
final class Extension implements BlueprintExtension
{
    /**
     * Default helpers to register (for backward compatibility).
     */
    private const DEFAULT_HELPERS = [
        'Helper_Title',
        'Helper_Breadcrumbs',
        'Helper_Html',
        'Helper_Assets',
        'Helper_Request',
        'Helper_Db',
        'Helper_Arr',
        'Helper_Number',
    ];

    /**
     * Register extension with Blueprint.
     */
    public function register(Blueprint $blueprint): void
    {
        $this->registerHelpersFunction($blueprint);
        $this->registerShorthandFunctions($blueprint);
    }

    /**
     * Register main helpers() function for dynamic access.
     */
    private function registerHelpersFunction(Blueprint $blueprint): void
    {
        $blueprint->registerFunction('helpers', fn(string $name): ?object => $this->resolveHelper($name));
        $blueprint->registerFunction('Helpers', fn(string $name): ?object => $this->resolveHelper($name));
        // Alias for backward compatibility with statics()
        $blueprint->registerFunction('statics', fn(string $name): ?object => $this->resolveHelper($name));
        $blueprint->registerFunction('Statics', fn(string $name): ?object => $this->resolveHelper($name));
    }

    /**
     * Register shorthand functions for discovered helpers.
     */
    private function registerShorthandFunctions(Blueprint $blueprint): void
    {
        foreach ($this->discoverHelpers() as $helperName) {
            $functionName = strtolower($helperName);
            $blueprint->registerFunction(
                $functionName,
                fn() => $this->resolveHelper($helperName)
            );
        }
    }

    /**
     * Discover available Helpers classes.
     *
     * @return array<string>
     */
    private function discoverHelpers(): array
    {
        $helpers = $this->discoverViaFacades();
        
        if (empty($helpers)) {
            $helpers = $this->discoverViaAutoload();
        }
        
        return array_unique($helpers);
    }

    /**
     * Discover helpers via Facade classes using HelperDiscovery.
     *
     * @return array<string>
     */
    private function discoverViaFacades(): array
    {
        $helpers = [];

        // Use HelperDiscovery to find all helpers implementing HelperInterface
        $discovery = new HelperDiscovery();
        $classes = $discovery->getDiscoveredClasses();
        
        foreach ($classes as $className) {
            if (!is_subclass_of($className, HelperInterface::class)) {
                continue;
            }
            // Determine facade class via getFacadeClass method
            if (method_exists($className, 'getFacadeClass')) {
                $facadeClass = call_user_func([$className, 'getFacadeClass']);
                if ($facadeClass && class_exists($facadeClass) && is_subclass_of($facadeClass, Facade::class)) {
                    // Get alias from helper class
                    if (method_exists($className, 'getAlias')) {
                        $alias = call_user_func([$className, 'getAlias']);
                    } else {
                        // Fallback to class name without namespace and prefix
                        $shortName = substr(strrchr($className, '\\'), 1);
                        if (str_starts_with($shortName, 'Helper_')) {
                            $shortName = substr($shortName, 7);
                        }
                        $alias = strtolower($shortName);
                    }
                    $helpers[] = $alias;
                }
            }
        }

        // Also include default helpers for backward compatibility
        foreach (self::DEFAULT_HELPERS as $helperName) {
            // Try old location first
            $facadeClass = "Architect\\Helpers\\Facades\\{$helperName}";
            if (!class_exists($facadeClass)) {
                // Try new location (HelperName/Facades/Helper_Name)
                $shortName = str_replace('Helper_', '', $helperName);
                $facadeClass = "Architect\\Helpers\\{$shortName}\\Facades\\{$helperName}";
            }
            
            if (class_exists($facadeClass) && is_subclass_of($facadeClass, Facade::class)) {
                $shortName = str_replace('Helper_', '', $helperName);
                if (!in_array($shortName, $helpers, true)) {
                    $helpers[] = $shortName;
                }
            }
        }

        return $helpers;
    }

    /**
     * Discover helpers via autoloader (fallback).
     *
     * @return array<string>
     */
    private function discoverViaAutoload(): array
    {
        $helpers = [];

        foreach (self::DEFAULT_HELPERS as $helperName) {
            // Try old location first
            $facadeClass = "Architect\\Helpers\\Facades\\{$helperName}";
            if (!class_exists($facadeClass)) {
                // Try new location
                $shortName = str_replace('Helper_', '', $helperName);
                $facadeClass = "Architect\\Helpers\\{$shortName}\\Facades\\{$helperName}";
            }
            
            if (class_exists($facadeClass)) {
                $shortName = str_replace('Helper_', '', $helperName);
                $helpers[] = $shortName;
            }
        }

        return $helpers;
    }

    /**
     * Resolve Helper instance by name.
     */
    private function resolveHelper(string $name): ?object
    {
        // Try new location first: Architect\Helpers\{HelperName}\Facades\Helper_{HelperName}
        $facadeClass = "Architect\\Helpers\\{$name}\\Facades\\Helper_{$name}";
        if (!class_exists($facadeClass)) {
            // Try old location: Architect\Helpers\Facades\Helper_{$name}
            $facadeClass = "Architect\\Helpers\\Facades\\Helper_{$name}";
            if (!class_exists($facadeClass)) {
                // Fallback to without prefix (for backward compatibility)
                $facadeClass = "Architect\\Helpers\\Facades\\{$name}";
                if (!class_exists($facadeClass)) {
                    return null;
                }
            }
        }

        // Ensure facade has container set
        if (!Facade::hasContainer()) {
            return null;
        }

        try {
            return $facadeClass::getFacadeRoot();
        } catch (\Throwable $e) {
            // Log error? For now, return null.
            return null;
        }
    }
}