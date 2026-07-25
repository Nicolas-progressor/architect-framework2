<?php

declare(strict_types=1);

namespace Architect\Services\Config\Contracts;

/**
 * Mutable configuration repository interface.
 *
 * Extends ConfigInterface with ability to modify configuration at runtime.
 */
interface MutableConfigInterface extends ConfigInterface
{
    /**
     * Set configuration value.
     *
     * @param string $key Configuration key (supports dot notation)
     * @param mixed $value Value to set
     */
    public function set(string $key, mixed $value): void;

    /**
     * Remove configuration key.
     *
     * @param string $key Configuration key (supports dot notation)
     */
    public function forget(string $key): void;

    /**
     * Replace all configuration data.
     *
     * @param array $data New configuration data
     */
    public function replace(array $data): void;
}
