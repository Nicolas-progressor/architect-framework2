<?php

declare(strict_types=1);

namespace Architect\Core\Config;

interface ConfigInterface
{
    /**
     * Get configuration value by key (dot notation).
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Get all configuration values.
     */
    public function all(): array;

    /**
     * Check if configuration is loaded.
     */
    public function isLoaded(): bool;
}