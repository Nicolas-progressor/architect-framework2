<?php

declare(strict_types=1);

namespace Axiom\Cache;

use Architect\Services\Cache\CacheManager as ArchitectCacheManager;
use Architect\Services\Cache\Config\CacheConfig as ArchitectCacheConfig;
use Architect\Services\Config\JsonConfigLoader;

/**
 * Cache driver that delegates to Architect Framework's cache system.
 * This driver requires Architect Framework to be available.
 */
class ArchitectCacheDriver implements CacheDriver
{
    private ?\Architect\Services\Cache\CacheInterface $store = null;
    private int $ttl;

    public function __construct(array $config, int $ttl = 3600)
    {
        $this->ttl = $ttl;

        // Check if Architect cache classes are available
        if (!class_exists(ArchitectCacheManager::class)) {
            throw new \RuntimeException(
                'Architect Framework cache classes not found. ' .
                'Please ensure Architect Framework is installed and autoloaded.'
            );
        }

        // Load configuration from Architect's cache.json if not provided
        $architectConfig = $config['architect_config'] ?? null;
        if ($architectConfig === null) {
            // Attempt to load default config
            $configLoader = new JsonConfigLoader(__DIR__ . '/../../../../app/config');
            $cacheConfig = $configLoader->load('cache');
            $architectConfig = new ArchitectCacheConfig($cacheConfig);
        } else {
            $architectConfig = new ArchitectCacheConfig($architectConfig);
        }

        $manager = new ArchitectCacheManager($architectConfig);
        $this->store = $manager->store();
    }

    public function get(string $key): mixed
    {
        return $this->store->get($key);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->store->set($key, $value, $ttl ?? $this->ttl);
    }

    public function has(string $key): bool
    {
        return $this->store->has($key);
    }

    public function delete(string $key): void
    {
        $this->store->delete($key);
    }

    public function flush(): void
    {
        $this->store->clear();
    }

    public function flushPattern(string $pattern): void
    {
        // Architect cache does not support pattern flush by default.
        // We could implement by iterating over keys, but for simplicity we ignore.
        // Optionally, we can clear entire cache if pattern is '*'
        if ($pattern === '*') {
            $this->flush();
        }
        // Otherwise, do nothing (limitation)
    }
}