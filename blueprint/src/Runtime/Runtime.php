<?php

declare(strict_types=1);

namespace Blueprint\Engine\Runtime;

use Blueprint\Engine\Contracts\FilterRegistryInterface;
use Blueprint\Engine\Contracts\FunctionRegistryInterface;
use Blueprint\Engine\Contracts\RuntimeInterface;

/**
 * Template Runtime Environment
 * 
 * Provides runtime functions for compiled templates.
 * Fully DI-based, no static methods.
 * 
 * @package Blueprint\Engine\Runtime
 */
class Runtime implements RuntimeInterface
{
    protected FilterRegistryInterface $filterRegistry;
    protected FunctionRegistryInterface $functionRegistry;

    public function __construct(
        FilterRegistryInterface $filterRegistry,
        FunctionRegistryInterface $functionRegistry
    ) {
        $this->filterRegistry = $filterRegistry;
        $this->functionRegistry = $functionRegistry;
    }

    // ============ Escaping ============

    /**
     * Escape value for HTML output
     */
    public function escape(mixed $value): string
    {
        return Escaper::escape($value);
    }

    // ============ Property Access ============

    /**
     * Get object/array property
     */
    public function getProperty(mixed $object, string $property): mixed
    {
        return PropertyAccessor::get($object, $property);
    }

    // ============ Method Calling ============

    /**
     * Call object method
     */
    public function callMethod(mixed $object, string $method, array $args = []): mixed
    {
        return MethodCaller::call($object, $method, $args);
    }

    /**
     * Call static method on a class
     */
    public function callStaticMethod(string $class, string $method, array $args = []): mixed
    {
        if (!class_exists($class)) {
            return null;
        }

        return forward_static_call_array([$class, $method], $args);
    }

    // ============ Filters ============

    /**
     * Apply filter to value
     */
    public function applyFilter(string $name, mixed $value, array $args = []): mixed
    {
        $filter = $this->filterRegistry->get($name);
        
        if ($filter !== null) {
            return $filter($value, ...$args);
        }

        return $value;
    }

    /**
     * Register filter
     */
    public function registerFilter(string $name, callable $filter): void
    {
        $this->filterRegistry->register($name, $filter);
    }

    /**
     * Get filter registry
     */
    public function getFilterRegistry(): FilterRegistryInterface
    {
        return $this->filterRegistry;
    }

    // ============ Functions ============

    /**
     * Call function
     */
    public function callFunction(string $name, array $args = [], array $context = []): mixed
    {
        $function = $this->functionRegistry->get($name);
        
        if ($function !== null) {
            return $function(...$args);
        }

        return null;
    }

    /**
     * Register function
     */
    public function registerFunction(string $name, callable $function): void
    {
        $this->functionRegistry->register($name, $function);
    }

    /**
     * Get function registry
     */
    public function getFunctionRegistry(): FunctionRegistryInterface
    {
        return $this->functionRegistry;
    }

    // ============ Template Inheritance ============

    /**
     * Render parent template with blocks
     */
    public function renderParent(string $parentTemplate, array $blocks, array $context, ?object $blueprint = null): string
    {
        $parentContext = $context;
        $parentContext['__blocks'] = $blocks;
        
        if ($blueprint && method_exists($blueprint, 'render')) {
            return $blueprint->render($parentTemplate, $parentContext);
        }
        
        return '';
    }

    // ============ Utility Methods ============

    /**
     * Check if value is empty
     */
    public function isEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }
        if (is_string($value) && trim($value) === '') {
            return true;
        }
        if (is_array($value) && empty($value)) {
            return true;
        }
        return false;
    }

    /**
     * Check if value is not empty
     */
    public function isNotEmpty(mixed $value): bool
    {
        return !$this->isEmpty($value);
    }

    /**
     * Convert value to string
     */
    public function toString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }
        return (string) $value;
    }
}

