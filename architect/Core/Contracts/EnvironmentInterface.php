<?php

declare(strict_types=1);

namespace Architect\Core\Contracts;

/**
 * Environment interface for application environment management.
 */
interface EnvironmentInterface
{
    /**
     * Get current environment name.
     */
    public function getEnvironment(): string;

    /**
     * Check if running in development mode.
     */
    public function isDevelopment(): bool;

    /**
     * Check if running in testing mode.
     */
    public function isTesting(): bool;

    /**
     * Check if running in staging mode.
     */
    public function isStaging(): bool;

    /**
     * Check if running in production mode.
     */
    public function isProduction(): bool;

    /**
     * Get configuration value by key (dot notation).
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Get all configuration values.
     */
    public function all(): array;
}
