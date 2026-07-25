<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Contracts;

/**
 * Interface for Model service.
 *
 * Defines the contract for model loading and management.
 *
 * @package Architect\Services\Mvc\Contracts
 */
interface ModelInterface
{
    /**
     * Load model by name.
     *
     * @param string $name Model name
     * @param string|bool|null $module Module name or true for global module
     * @return object|null Model instance or null if not found
     */
    public function load(string $name, $module = null): ?object;

    /**
     * Create model instance by name.
     *
     * Alias for load() method.
     *
     * @param string $name Model name
     * @param string|null $module Module name
     * @return object|null Model instance or null if not found
     */
    public function create(string $name, ?string $module = null): ?object;

    /**
     * Set current module.
     *
     * @param string $module Module name
     */
    public function setModule(string $module): void;

    /**
     * Get service or loaded model.
     *
     * Returns service from container for known service names,
     * otherwise returns loaded model from cache.
     *
     * @param string $id Service identifier or model name
     * @return mixed
     */
    public function get(string $id): mixed;
}
