<?php

declare(strict_types=1);

namespace Architect\Services\Routing\Contracts;

interface RouteCacheInterface
{
    /**
     * Check if routes are cached for an application.
     */
    public function has(string $appDir): bool;

    /**
     * Get cached routes for an application.
     */
    public function get(string $appDir): ?array;

    /**
     * Store routes in cache.
     */
    public function put(string $appDir, array $routes): void;

    /**
     * Clear route cache.
     */
    public function clear(): void;

    /**
     * Clear cache for specific application.
     */
    public function clearFor(string $appDir): void;

    /**
     * Get cache key for application routes.
     */
    public function getCacheKey(string $appDir): string;

    /**
     * Check if cache is enabled.
     */
    public function isEnabled(): bool;

    /**
     * Enable or disable cache.
     */
    public function setEnabled(bool $enabled): void;
}
