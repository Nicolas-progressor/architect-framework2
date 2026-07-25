<?php

declare(strict_types=1);

namespace Blueprint\Engine\Filters;

use Blueprint\Engine\Contracts\FilterRegistryInterface;

/**
 * Filter Registry
 * 
 * Manages filter registration and retrieval.
 * Supports lazy loading of filter groups.
 * 
 * @package Blueprint\Engine\Filters
 */
class FilterRegistry implements FilterRegistryInterface
{
    /**
     * Registered filters
     * 
     * @var array<string, callable>
     */
    protected array $filters = [];

    /**
     * Filter groups (lazy loaded)
     * 
     * @var array<string, class-string>
     */
    protected array $groups = [];

    /**
     * Loaded groups cache
     * 
     * @var array<string, bool>
     */
    protected array $loadedGroups = [];

    /**
     * Constructor - register default filters
     */
    public function __construct()
    {
        $this->registerDefaultGroups();
        $this->registerDefaultFilters();
    }

    /**
     * Register default filter groups
     */
    protected function registerDefaultGroups(): void
    {
        $this->groups = [
            'string' => StringFilters::class,
            'array' => ArrayFilters::class,
            'number' => NumberFilters::class,
            'date' => DateFilters::class,
            'conversion' => ConversionFilters::class,
            'type' => TypeFilters::class,
        ];
    }

    /**
     * Register default filters (core ones that are always loaded)
     */
    protected function registerDefaultFilters(): void
    {
        // Essential filters always available
        $this->filters = [
            'raw' => fn($value) => (string) $value,
            'escape' => fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'),
            'e' => fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'),
            'default' => fn($value, $default = '') => $value ?? $default ?: $default,
        ];
    }

    /**
     * Register a filter
     * 
     * @param string $name Filter name
     * @param callable $filter Filter callable
     */
    public function register(string $name, callable $filter): void
    {
        $this->filters[$name] = $filter;
    }

    /**
     * Register multiple filters
     * 
     * @param array<string, callable> $filters Filters to register
     */
    public function registerMany(array $filters): void
    {
        foreach ($filters as $name => $filter) {
            $this->register($name, $filter);
        }
    }

    /**
     * Get filter by name
     * 
     * @param string $name Filter name
     * @return callable|null
     */
    public function get(string $name): ?callable
    {
        // Check if already loaded
        if (isset($this->filters[$name])) {
            return $this->filters[$name];
        }

        // Try to load from groups
        $this->loadFilterFromGroups($name);

        return $this->filters[$name] ?? null;
    }

    /**
     * Check if filter exists
     * 
     * @param string $name Filter name
     * @return bool
     */
    public function has(string $name): bool
    {
        if (isset($this->filters[$name])) {
            return true;
        }

        // Try to load from groups
        $this->loadFilterFromGroups($name);

        return isset($this->filters[$name]);
    }

    /**
     * Get all registered filters
     * 
     * @return array<string, callable>
     */
    public function getAll(): array
    {
        // Load all groups
        foreach ($this->groups as $groupName => $class) {
            $this->loadGroup($groupName);
        }

        return $this->filters;
    }

    /**
     * Remove a filter
     * 
     * @param string $name Filter name
     */
    public function remove(string $name): void
    {
        unset($this->filters[$name]);
    }

    /**
     * Clear all filters
     */
    public function clear(): void
    {
        $this->filters = [];
        $this->loadedGroups = [];
    }

    /**
     * Load filter from groups
     * 
     * @param string $name Filter name
     */
    protected function loadFilterFromGroups(string $name): void
    {
        foreach ($this->groups as $groupName => $class) {
            if (isset($this->loadedGroups[$groupName])) {
                continue;
            }

            // Check if this group might have the filter
            if (method_exists($class, $name) || $this->groupHasFilter($class, $name)) {
                $this->loadGroup($groupName);
                
                if (isset($this->filters[$name])) {
                    return;
                }
            }
        }
    }

    /**
     * Check if group class has filter
     */
    protected function groupHasFilter(string $class, string $name): bool
    {
        // Convention: filters are registered as static methods
        return method_exists($class, $name);
    }

    /**
     * Load a filter group
     * 
     * @param string $groupName Group name
     */
    protected function loadGroup(string $groupName): void
    {
        if (isset($this->loadedGroups[$groupName])) {
            return;
        }

        $class = $this->groups[$groupName] ?? null;
        if ($class === null || !class_exists($class)) {
            $this->loadedGroups[$groupName] = true;
            return;
        }

        // Get filters from group class
        if (method_exists($class, 'getFilters')) {
            $filters = $class::getFilters();
            foreach ($filters as $name => $filter) {
                if (!isset($this->filters[$name])) {
                    $this->filters[$name] = $filter;
                }
            }
        }

        $this->loadedGroups[$groupName] = true;
    }

    /**
     * Register a custom filter group
     * 
     * @param string $name Group name
     * @param string $class Class name with getFilters() method
     */
    public function registerGroup(string $name, string $class): void
    {
        $this->groups[$name] = $class;
    }
}
