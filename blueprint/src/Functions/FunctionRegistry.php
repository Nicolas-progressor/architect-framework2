<?php

declare(strict_types=1);

namespace Blueprint\Engine\Functions;

use Blueprint\Engine\Contracts\FunctionRegistryInterface;

/**
 * Function Registry
 * 
 * Manages function registration and retrieval.
 * Supports lazy loading of function groups.
 * 
 * @package Blueprint\Engine\Functions
 */
class FunctionRegistry implements FunctionRegistryInterface
{
    /**
     * Registered functions
     * 
     * @var array<string, callable>
     */
    protected array $functions = [];

    /**
     * Function groups (lazy loaded)
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
     * Constructor - register default functions
     */
    public function __construct()
    {
        $this->registerDefaultGroups();
        $this->registerDefaultFunctions();
    }

    /**
     * Register default function groups
     */
    protected function registerDefaultGroups(): void
    {
        $this->groups = [
            'array' => ArrayFunctions::class,
            'string' => StringFunctions::class,
            'math' => MathFunctions::class,
            'date' => DateFunctions::class,
            'url' => UrlFunctions::class,
            'debug' => DebugFunctions::class,
            'conversion' => ConversionFunctions::class,
        ];
    }

    /**
     * Register default functions (core ones that are always loaded)
     */
    protected function registerDefaultFunctions(): void
    {
        // Essential functions always available
        $this->functions = [
            'empty' => fn($value) => empty($value),
            'isset' => fn(...$vars) => !in_array(null, $vars, true),
            'defined' => fn($name) => defined($name),
        ];
    }

    /**
     * Register a function
     * 
     * @param string $name Function name
     * @param callable $function Function callable
     */
    public function register(string $name, callable $function): void
    {
        $this->functions[$name] = $function;
    }

    /**
     * Register multiple functions
     * 
     * @param array<string, callable> $functions Functions to register
     */
    public function registerMany(array $functions): void
    {
        foreach ($functions as $name => $function) {
            $this->register($name, $function);
        }
    }

    /**
     * Get function by name
     * 
     * @param string $name Function name
     * @return callable|null
     */
    public function get(string $name): ?callable
    {
        // Check if already loaded
        if (isset($this->functions[$name])) {
            return $this->functions[$name];
        }

        // Try to load from groups
        $this->loadFunctionFromGroups($name);

        return $this->functions[$name] ?? null;
    }

    /**
     * Check if function exists
     * 
     * @param string $name Function name
     * @return bool
     */
    public function has(string $name): bool
    {
        if (isset($this->functions[$name])) {
            return true;
        }

        // Try to load from groups
        $this->loadFunctionFromGroups($name);

        return isset($this->functions[$name]);
    }

    /**
     * Get all registered functions
     * 
     * @return array<string, callable>
     */
    public function getAll(): array
    {
        // Load all groups
        foreach ($this->groups as $groupName => $class) {
            $this->loadGroup($groupName);
        }

        return $this->functions;
    }

    /**
     * Remove a function
     * 
     * @param string $name Function name
     */
    public function remove(string $name): void
    {
        unset($this->functions[$name]);
    }

    /**
     * Clear all functions
     */
    public function clear(): void
    {
        $this->functions = [];
        $this->loadedGroups = [];
    }

    /**
     * Load function from groups
     * 
     * @param string $name Function name
     */
    protected function loadFunctionFromGroups(string $name): void
    {
        foreach ($this->groups as $groupName => $class) {
            if (isset($this->loadedGroups[$groupName])) {
                continue;
            }

            // Check if this group might have the function
            if (method_exists($class, $name) || $this->groupHasFunction($class, $name)) {
                $this->loadGroup($groupName);
                
                if (isset($this->functions[$name])) {
                    return;
                }
            }
        }
    }

    /**
     * Check if group class has function
     */
    protected function groupHasFunction(string $class, string $name): bool
    {
        return method_exists($class, $name);
    }

    /**
     * Load a function group
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

        // Get functions from group class
        if (method_exists($class, 'getFunctions')) {
            $functions = $class::getFunctions();
            foreach ($functions as $name => $function) {
                if (!isset($this->functions[$name])) {
                    $this->functions[$name] = $function;
                }
            }
        }

        $this->loadedGroups[$groupName] = true;
    }

    /**
     * Register a custom function group
     * 
     * @param string $name Group name
     * @param string $class Class name with getFunctions() method
     */
    public function registerGroup(string $name, string $class): void
    {
        $this->groups[$name] = $class;
    }
}
