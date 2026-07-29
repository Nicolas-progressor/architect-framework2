<?php

declare(strict_types=1);

namespace Architect\Services\Routing\Contracts;

/**
 * Interface for Router service.
 */
interface RouterInterface
{
    /**
     * Load routes from application directory.
     */
    public function loadRoutes(string $appDir): void;

    /**
     * Check if route is found.
     */
    public function hasRoute(): bool;

    /**
     * Get URL segment by index.
     */
    public function segment(int $index, string $default = ''): string;

    /**
     * Get module name.
     */
    public function getModule(): string;

    /**
     * Get controller name.
     */
    public function getController(): string;

    /**
     * Get action name.
     */
    public function getAction(): string;

    /**
     * Get parameter by name.
     */
    public function getParam(string $name, string $default = ''): string;

    /**
     * Get current path.
     */
    public function getPath(): string;

    /**
     * Register a named route.
     *
     * @param string $name  Route name
     * @param string $path  URL path pattern
     * @param array  $route Route definition
     */
    public function name(string $name, string $path, array $route = []): static;

    /**
     * Generate URL for a named route.
     *
     * @param string $name   Route name
     * @param array  $params Parameters for placeholders
     */
    public function route(string $name, array $params = []): string;

    /**
     * Check if a named route exists.
     */
    public function hasNamedRoute(string $name): bool;

    /**
     * Get all named routes.
     *
     * @return array<string, array{path: string, route: array}>
     */
    public function getNamedRoutes(): array;

    /**
     * Define a route group.
     *
     * @param string   $prefix   Route prefix
     * @param array    $options  Options: middleware, namespace
     * @param callable $callback Closure receiving the router
     */
    public function group(string $prefix, array $options, callable $callback): static;

    /**
     * Register a route with middleware.
     *
     * @param string $path      Route path
     * @param array  $route     Route definition
     * @param array  $middleware Middleware names
     */
    public function routeMiddleware(string $path, array $route, array $middleware = []): static;

    /**
     * Get middleware for the resolved route.
     *
     * @return array<int, string>
     */
    public function getRouteMiddleware(): array;

    /**
     * Match a URL pattern with placeholders.
     *
     * @param string $pattern Route pattern (e.g. 'users/{id}')
     * @param string $path    Actual URL path
     * @return array|null Matched parameters or null
     */
    public function matchPattern(string $pattern, string $path): ?array;
}
