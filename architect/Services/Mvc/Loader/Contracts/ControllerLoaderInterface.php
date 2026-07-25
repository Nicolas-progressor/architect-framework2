<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Loader\Contracts;

use Architect\Services\Mvc\Contracts\ControllerInterface;

/**
 * Interface for controller loading.
 * 
 * Defines the contract for loading and instantiating controllers.
 * 
 * @package Architect\Services\Mvc\Loader\Contracts
 */
interface ControllerLoaderInterface
{
    /**
     * Load controller by module and name.
     * 
     * @param string $module Module name
     * @param string $controller Controller name
     * @param bool $isGlobal Whether module is global
     * @return ControllerInterface|null Controller instance or null
     */
    public function load(string $module, string $controller, bool $isGlobal = false): ?ControllerInterface;

    /**
     * Check if controller exists.
     * 
     * @param string $module Module name
     * @param string $controller Controller name
     * @param bool $isGlobal Whether module is global
     * @return bool
     */
    public function exists(string $module, string $controller, bool $isGlobal = false): bool;

    /**
     * Get controller file path.
     * 
     * @param string $module Module name
     * @param string $controller Controller name
     * @param bool $isGlobal Whether module is global
     * @return string|null File path or null
     */
    public function getFilePath(string $module, string $controller, bool $isGlobal = false): ?string;
}
