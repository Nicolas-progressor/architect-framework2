<?php

declare(strict_types=1);

namespace Architect\Services\Blueprint\Contracts;

/**
 * Interface for Blueprint configuration management
 */
interface BlueprintConfigInterface
{
    /**
     * Get configuration value by key
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Get all configuration values
     */
    public function all(): array;

    /**
     * Check if key exists
     */
    public function has(string $key): bool;

    /**
     * Get template paths
     */
    public function getPaths(): array;

    /**
     * Get cache path
     */
    public function getCachePath(): ?string;

    /**
     * Is cache enabled
     */
    public function isCacheEnabled(): bool;

    /**
     * Is debug mode enabled
     */
    public function isDebug(): bool;

    /**
     * Get file extensions
     */
    public function getExtensions(): array;
}
