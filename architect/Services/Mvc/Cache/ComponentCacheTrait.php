<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Cache;

/**
 * Component cache trait.
 * 
 * Provides caching functionality for MVC component loaders.
 * 
 * @package Architect\Services\Mvc\Cache
 */
trait ComponentCacheTrait
{
    /** @var array<string, object> Cached component instances */
    private array $componentCache = [];

    /**
     * Get cached instance.
     * 
     * @param string $key Cache key
     * @return object|null Cached instance or null
     */
    protected function getCached(string $key): ?object
    {
        return $this->componentCache[$key] ?? null;
    }

    /**
     * Set cached instance.
     * 
     * @param string $key Cache key
     * @param object $instance Instance to cache
     */
    protected function setCached(string $key, object $instance): void
    {
        $this->componentCache[$key] = $instance;
    }

    /**
     * Check if key exists in cache.
     * 
     * @param string $key Cache key
     * @return bool
     */
    protected function hasCached(string $key): bool
    {
        return isset($this->componentCache[$key]);
    }

    /**
     * Remove cached instance.
     * 
     * @param string $key Cache key
     */
    protected function removeCached(string $key): void
    {
        unset($this->componentCache[$key]);
    }

    /**
     * Clear all cached instances.
     */
    public function clearCache(): void
    {
        $this->componentCache = [];
    }

    /**
     * Get all cached keys.
     * 
     * @return array<string>
     */
    public function getCachedKeys(): array
    {
        return array_keys($this->componentCache);
    }

    /**
     * Get cache count.
     * 
     * @return int
     */
    public function getCacheCount(): int
    {
        return count($this->componentCache);
    }
}
