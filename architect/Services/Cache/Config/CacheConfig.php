<?php

declare(strict_types=1);

namespace Architect\Services\Cache\Config;

use Architect\Services\Config\Contracts\ConfigInterface;

/**
 * Cache configuration wrapper.
 */
class CacheConfig
{
    /**
     * @var ConfigInterface
     */
    private ConfigInterface $config;

    /**
     * @param ConfigInterface $config Configuration service (e.g., from 'cache' namespace)
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Get default cache store name.
     */
    public function getDefaultStore(): string
    {
        return $this->config->get('default', 'file');
    }

    /**
     * Get configuration for a specific store.
     *
     * @param string|null $store Store name, null for default
     * @return array<string, mixed>
     * @throws \InvalidArgumentException If store not defined.
     */
    public function getStoreConfig(?string $store = null): array
    {
        $store = $store ?? $this->getDefaultStore();
        $stores = $this->config->get('stores', []);

        if (!isset($stores[$store])) {
            throw new \InvalidArgumentException(
                sprintf('Cache store "%s" is not defined.', $store)
            );
        }

        return $stores[$store];
    }

    /**
     * Get the driver name for a store.
     */
    public function getDriver(string $store): string
    {
        $config = $this->getStoreConfig($store);
        return $config['driver'] ?? 'file';
    }

    /**
     * Get global cache prefix.
     */
    public function getPrefix(): string
    {
        return $this->config->get('prefix', 'arch_cache_');
    }

    /**
     * Get default TTL in seconds.
     */
    public function getDefaultTtl(): int
    {
        return (int) $this->config->get('ttl', 3600);
    }

    /**
     * Get all store names.
     *
     * @return array<string>
     */
    public function getStoreNames(): array
    {
        $stores = $this->config->get('stores', []);
        return array_keys($stores);
    }

    /**
     * Check if a store exists.
     */
    public function hasStore(string $store): bool
    {
        $stores = $this->config->get('stores', []);
        return isset($stores[$store]);
    }
}