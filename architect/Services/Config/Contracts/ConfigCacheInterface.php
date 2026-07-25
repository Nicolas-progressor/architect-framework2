<?php

declare(strict_types=1);

namespace Architect\Services\Config\Contracts;

interface ConfigCacheInterface
{
    /**
     * Check if configuration is cached.
     */
    public function has(string $name, ?string $appPath = null): bool;

    /**
     * Get cached configuration.
     */
    public function get(string $name, ?string $appPath = null): ?ConfigInterface;

    /**
     * Store configuration in cache.
     */
    public function put(string $name, ConfigInterface $config, ?string $appPath = null): void;

    /**
     * Clear configuration cache.
     */
    public function clear(): void;

    /**
     * Clear cache for specific configuration.
     */
    public function clearFor(string $name, ?string $appPath = null): void;

    /**
     * Get cache key for configuration.
     */
    public function getCacheKey(string $name, ?string $appPath = null): string;

    /**
     * Check if cache is enabled.
     */
    public function isEnabled(): bool;

    /**
     * Enable or disable cache.
     */
    public function setEnabled(bool $enabled): void;
}
