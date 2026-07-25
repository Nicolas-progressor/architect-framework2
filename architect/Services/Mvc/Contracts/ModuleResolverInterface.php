<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Contracts;

/**
 * Interface for module path resolution.
 *
 * Defines the contract for resolving module paths for
 * controllers, models, views, and other resources.
 *
 * @package Architect\Services\Mvc\Contracts
 */
interface ModuleResolverInterface
{
    /**
     * Resolve module path.
     *
     * @param string $module Module name
     * @param string $type Resource type (controller, model, view)
     * @param bool $isGlobal Whether to resolve global module path
     * @return string Resolved path
     */
    public function resolvePath(string $module, string $type, bool $isGlobal = false): string;

    /**
     * Check if module exists.
     *
     * @param string $module Module name
     * @param bool $isGlobal Whether to check global modules
     * @return bool
     */
    public function moduleExists(string $module, bool $isGlobal = false): bool;

    /**
     * Check if module is global.
     *
     * Returns true if module exists only in global modules.
     *
     * @param string $module Module name
     * @return bool
     */
    public function isGlobalModule(string $module): bool;

    /**
     * Get controller file path.
     *
     * @param string $module Module name
     * @param string $controller Controller name
     * @param bool $isGlobal Whether to search in global modules
     * @return string|null Controller file path or null if not found
     */
    public function getControllerPath(string $module, string $controller, bool $isGlobal = false): ?string;

    /**
     * Get model file path.
     *
     * @param string $module Module name
     * @param string $model Model name
     * @param bool $isGlobal Whether to search in global modules
     * @return string|null Model file path or null if not found
     */
    public function getModelPath(string $module, string $model, bool $isGlobal = false): ?string;

    /**
     * Get view directory path.
     *
     * @param string $module Module name
     * @param bool $isGlobal Whether to get global module path
     * @return string View directory path
     */
    public function getViewPath(string $module, bool $isGlobal = false): string;
}
