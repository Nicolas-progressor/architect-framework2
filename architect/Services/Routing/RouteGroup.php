<?php

declare(strict_types=1);

namespace Architect\Services\Routing;

/**
 * Route Group — groups routes with shared prefix, middleware, and namespace.
 *
 * Usage:
 *   $group = new RouteGroup('admin', ['middleware' => 'auth', 'namespace' => 'Admin']);
 *   $group->addRoute('dashboard', [...]);
 *   $group->addRoute('settings', [...]);
 *   $group->getRoutes(); // flattened routes with prefix applied
 */
class RouteGroup
{
    private string $prefix;

    /** @var array<string, string|string[]> */
    private array $middleware = [];

    private string $namespace = '';

    /** @var array<string, array> */
    private array $routes = [];

    /**
     * @param string $prefix Route prefix (e.g. 'admin', 'api/v1')
     * @param array{prefix?: string, middleware?: string|string[], namespace?: string} $options
     */
    public function __construct(string $prefix = '', array $options = [])
    {
        $this->prefix = trim($prefix, '/');

        $middleware = $options['middleware'] ?? [];
        $this->middleware = is_string($middleware) ? [$middleware] : $middleware;

        $this->namespace = $options['namespace'] ?? '';
    }

    /**
     * Add a route to the group.
     *
     * @param string $path      Route path (relative to group prefix)
     * @param array  $route     Route definition [module, controller, action, ...]
     * @param string $name      Optional route name
     * @param array  $options   Per-route options (middleware, etc.)
     * @return $this
     */
    public function addRoute(string $path, array $route, string $name = '', array $options = []): static
    {
        $fullPath = $this->buildPath($path);

        $route['middleware'] = array_unique(array_merge(
            $this->middleware,
            is_string($options['middleware'] ?? null) ? [$options['middleware']] : ($options['middleware'] ?? [])
        ));

        if ($this->namespace !== '' && !isset($route['namespace'])) {
            $route['namespace'] = $this->namespace;
        }

        if ($name !== '') {
            $route['_name'] = $name;
        }

        $this->routes[$fullPath] = $route;

        return $this;
    }

    /**
     * Get all routes with group prefix applied.
     *
     * @return array<string, array>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get the group prefix.
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Get the group middleware.
     *
     * @return array<string, string>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Get the group namespace.
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Get number of routes in group.
     */
    public function count(): int
    {
        return count($this->routes);
    }

    /**
     * Check if group has any routes.
     */
    public function isEmpty(): bool
    {
        return $this->routes === [];
    }

    /**
     * Build full path by joining prefix and path.
     */
    private function buildPath(string $path): string
    {
        $path = trim($path, '/');

        if ($this->prefix === '') {
            return $path;
        }

        return $this->prefix . ($path !== '' ? '/' . $path : '');
    }
}
