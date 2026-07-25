<?php

declare(strict_types=1);

namespace Architect\Services\Config\Contracts;

/**
 * Immutable configuration repository interface.
 * 
 * Provides read-only access to configuration data with dot notation support.
 */
interface ConfigInterface
{
    /**
     * Get configuration value by key.
     * 
     * Supports dot notation: 'database.host' returns $data['database']['host']
     * 
     * @param string $key Configuration key (supports dot notation)
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if configuration key exists.
     * 
     * @param string $key Configuration key (supports dot notation)
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Get all configuration data.
     * 
     * @return array
     */
    public function all(): array;
}
