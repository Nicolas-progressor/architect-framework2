<?php

declare(strict_types=1);

namespace Axiom\Cache;

use Axiom\Orm\Query\QueryBuilder;

/**
 * Cache trait for QueryBuilder
 */
trait Cacheable
{
    private ?int $cacheTtl = null;
    private ?string $cacheKey = null;
    private bool $cacheDisabled = false;

    /**
     * Cache the query result
     */
    public function cache(?int $ttl = null): self
    {
        $this->cacheTtl = $ttl;
        return $this;
    }

    /**
     * Cache with custom key
     */
    public function remember(string $key, ?int $ttl = null): self
    {
        $this->cacheKey = $key;
        $this->cacheTtl = $ttl;
        return $this;
    }

    /**
     * Disable cache for this query
     */
    public function disableCache(): self
    {
        $this->cacheDisabled = true;
        return $this;
    }

    /**
     * Enable cache for this query
     */
    public function enableCache(): self
    {
        $this->cacheDisabled = false;
        return $this;
    }

    /**
     * Check if caching is enabled
     */
    public function isCacheEnabled(): bool
    {
        return !$this->cacheDisabled && CacheManager::isEnabled();
    }

    /**
     * Get cache TTL
     */
    public function getCacheTtl(): ?int
    {
        return $this->cacheTtl;
    }

    /**
     * Get cache key
     */
    public function getCacheKey(): ?string
    {
        return $this->cacheKey;
    }

    /**
     * Get cached result or execute query
     */
    protected function getCached(): array
    {
        if (!$this->isCacheEnabled()) {
            return $this->executeGet();
        }

        $key = $this->cacheKey ?? CacheManager::generateKey($this);
        
        $cached = CacheManager::get($key);
        
        if ($cached !== null) {
            return $cached;
        }

        $result = $this->executeGet();
        
        CacheManager::set($key, $result, $this->cacheTtl);
        
        return $result;
    }

    /**
     * Get first cached result or execute query
     */
    protected function firstCached(): ?array
    {
        $this->limit(1);
        $results = $this->getCached();
        return $results[0] ?? null;
    }

    /**
     * Execute get (to be implemented in QueryBuilder)
     */
    protected function executeGet(): array
    {
        // This will be overridden in QueryBuilder
        throw new \RuntimeException('executeGet must be implemented');
    }
}

/**
 * QueryBuilder extension with caching
 */
class CacheQueryBuilder extends QueryBuilder
{
    use Cacheable;

    protected function executeGet(): array
    {
        $sql = $this->buildSelect();
        $bindings = $this->collectBindings();

        $stmt = $this->getConnection()->query($sql, $bindings);
        $results = $stmt->fetchAll();

        if ($this->getEntityClass() && class_exists($this->getEntityClass())) {
            return $this->mapToEntities($results);
        }

        return $results;
    }

    /**
     * Override get to use cache
     */
    public function get(): array
    {
        if ($this->isCacheEnabled() && $this->getCacheTtl() !== null) {
            return $this->getCached();
        }

        return $this->executeGet();
    }

    /**
     * Override first to use cache
     */
    public function first(): ?array
    {
        if ($this->isCacheEnabled() && $this->getCacheTtl() !== null) {
            return $this->firstCached();
        }

        $this->limit(1);
        $results = $this->executeGet();
        return $results[0] ?? null;
    }
}

/**
 * Helper for cache management
 */
class Cache
{
    /**
     * Get cached value
     */
    public static function get(string $key): mixed
    {
        return CacheManager::get($key);
    }

    /**
     * Set cached value
     */
    public static function set(string $key, mixed $value, ?int $ttl = null): void
    {
        CacheManager::set($key, $value, $ttl);
    }

    /**
     * Check if cached
     */
    public static function has(string $key): bool
    {
        return CacheManager::has($key);
    }

    /**
     * Forget cached value
     */
    public static function forget(string $key): void
    {
        CacheManager::delete($key);
    }

    /**
     * Clear all cache
     */
    public static function flush(): void
    {
        CacheManager::flush();
    }

    /**
     * Clear cache by pattern
     */
    public static function flushPattern(string $pattern): void
    {
        CacheManager::flushPattern($pattern);
    }

    /**
     * Configure cache
     */
    public static function configure(array $config): void
    {
        CacheManager::configure($config);
    }

    /**
     * Check if cache is enabled
     */
    public static function isEnabled(): bool
    {
        return CacheManager::isEnabled();
    }
}
