<?php

declare(strict_types=1);

namespace Blueprint\Engine;

use Blueprint\Engine\Runtime\Runtime;
use Blueprint\Engine\Filters\FilterRegistry;
use Blueprint\Engine\Functions\FunctionRegistry;
use Blueprint\Engine\Contracts\RuntimeInterface;
use Blueprint\Engine\Contracts\FilterRegistryInterface;
use Blueprint\Engine\Contracts\FunctionRegistryInterface;

/**
 * Runtime Factory
 * 
 * Creates Runtime instance with all dependencies.
 * 
 * @package Blueprint\Engine
 */
class RuntimeFactory
{
    /**
     * Create Runtime with default registries
     */
    public static function create(
        ?FilterRegistryInterface $filterRegistry = null,
        ?FunctionRegistryInterface $functionRegistry = null
    ): RuntimeInterface {
        $filterRegistry ??= new FilterRegistry();
        $functionRegistry ??= new FunctionRegistry();
        
        return new Runtime($filterRegistry, $functionRegistry);
    }
    
    /**
     * Create Runtime with custom configuration
     */
    public static function createWithConfig(array $config): RuntimeInterface
    {
        $filterRegistry = new FilterRegistry();
        $functionRegistry = new FunctionRegistry();
        
        // Register custom filters
        if (!empty($config['filters']) && is_array($config['filters'])) {
            foreach ($config['filters'] as $name => $filter) {
                if (is_callable($filter)) {
                    $filterRegistry->register($name, $filter);
                }
            }
        }
        
        // Register custom functions
        if (!empty($config['functions']) && is_array($config['functions'])) {
            foreach ($config['functions'] as $name => $function) {
                if (is_callable($function)) {
                    $functionRegistry->register($name, $function);
                }
            }
        }
        
        // Register custom filter groups
        if (!empty($config['filter_groups']) && is_array($config['filter_groups'])) {
            foreach ($config['filter_groups'] as $name => $className) {
                $filterRegistry->registerGroup($name, $className);
            }
        }
        
        // Register custom function groups
        if (!empty($config['function_groups']) && is_array($config['function_groups'])) {
            foreach ($config['function_groups'] as $name => $className) {
                $functionRegistry->registerGroup($name, $className);
            }
        }
        
        return new Runtime($filterRegistry, $functionRegistry);
    }
}
