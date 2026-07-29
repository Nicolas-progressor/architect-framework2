<?php

declare(strict_types=1);

namespace Architect\Helpers\Core;

use Architect\Contracts\Core\ContainerInterface;

/**
 * Manager for registering and resolving helpers with lazy registration.
 */
class HelperManager
{
    protected ContainerInterface $container;
    protected array $bindings = [];
    protected array $instances = [];
    protected array $customMapping = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Register a helper with the container.
     *
     * @param string $alias Alias (e.g., 'title', 'breadcrumbs')
     * @param string $concrete Fully qualified class name
     */
    public function register(string $alias, string $concrete): void
    {
        $this->bindings[$alias] = $concrete;
        $this->container->bind($alias, $concrete);
    }

    /**
     * Register multiple helpers from a configuration array.
     *
     * @param array<string, string> $helpers
     */
    public function registerMany(array $helpers): void
    {
        foreach ($helpers as $alias => $concrete) {
            $this->register($alias, $concrete);
        }
    }

    /**
     * Get a helper instance by alias.
     */
    public function get(string $alias): object
    {
        if (isset($this->instances[$alias])) {
            return $this->instances[$alias];
        }

        $this->ensureRegistered($alias);

        $instance = $this->container->get($alias);
        $this->instances[$alias] = $instance;
        return $instance;
    }

    /**
     * Ensure a helper is registered; if not, attempt to discover and register it.
     */
    public function ensureRegistered(string $alias): void
    {
        if ($this->has($alias)) {
            return;
        }

        $class = $this->resolveClassByAlias($alias);
        if ($class === null) {
            throw new \RuntimeException("Helper with alias '{$alias}' could not be resolved.");
        }

        $this->register($alias, $class);
    }

    /**
     * Resolve helper class name by alias using naming convention.
     *
     * Convention:
     * - Alias 'title' => Architect\Helpers\Title\TitleHelper
     * - Alias 'html'  => Architect\Helpers\Html\HtmlHelper
     * - etc.
     *
     * Also supports custom mapping via register().
     */
    private function resolveClassByAlias(string $alias): ?string
    {
        // First, check if there's a custom binding (should already be registered via has)
        if (isset($this->bindings[$alias])) {
            return $this->bindings[$alias];
        }

        // Convert alias to studly case (e.g., 'title' -> 'Title', 'breadcrumbs' -> 'Breadcrumbs')
        $studly = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $alias)));

        // Try Architect\Helpers\{Studly}\{Studly}Helper
        $candidate = "Architect\\Helpers\\{$studly}\\{$studly}Helper";
        if (class_exists($candidate)) {
            return $candidate;
        }

        // Try Architect\Helpers\{Studly}Helper (for flat structure, though not used)
        $candidate = "Architect\\Helpers\\{$studly}Helper";
        if (class_exists($candidate)) {
            return $candidate;
        }

        // Try with 'Helper_' prefix (some older helpers)
        $candidate = "Architect\\Helpers\\{$studly}\\Helper_{$studly}";
        if (class_exists($candidate)) {
            return $candidate;
        }

        // If nothing found, return null
        return null;
    }

    /**
     * Check if a helper is registered.
     */
    public function has(string $alias): bool
    {
        return isset($this->bindings[$alias]);
    }

    /**
     * Get all registered aliases.
     *
     * @return array<string>
     */
    public function aliases(): array
    {
        return array_keys($this->bindings);
    }

    /**
     * Clear cached instances (for testing).
     */
    public function clearInstances(): void
    {
        $this->instances = [];
    }

    /**
     * Get the container instance.
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
