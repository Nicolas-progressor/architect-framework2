<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Loader\Contracts;

/**
 * Interface for module bootstrap loading.
 * 
 * Defines the contract for loading and initializing module bootstraps.
 * 
 * @package Architect\Services\Mvc\Loader\Contracts
 */
interface ModuleBootstrapLoaderInterface
{
    /**
     * Load module bootstrap.
     * 
     * @param string $module Module name
     * @param bool $isGlobal Whether module is global
     * @return object|null Bootstrap instance or null
     */
    public function load(string $module, bool $isGlobal = false): ?object;

    /**
     * Check if module bootstrap exists.
     * 
     * @param string $module Module name
     * @param bool $isGlobal Whether module is global
     * @return bool
     */
    public function exists(string $module, bool $isGlobal = false): bool;

    /**
     * Register bootstrap statement handlers.
     * 
     * @param object $bootstrap Bootstrap instance
     */
    public function registerStatementHandlers(object $bootstrap): void;
}
