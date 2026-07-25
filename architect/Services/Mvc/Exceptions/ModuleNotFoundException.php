<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Exceptions;

/**
 * Exception thrown when module cannot be found.
 *
 * Thrown when the specified module directory does not exist
 * in either application or global modules.
 *
 * @package Architect\Services\Mvc\Exceptions
 */
class ModuleNotFoundException extends MvcException
{
    /** @var string Module name */
    private string $module;

    /** @var bool Whether the module was searched in global modules */
    private bool $isGlobal;

    /**
     * Create exception for missing module.
     *
     * @param string $module Module name
     * @param bool $isGlobal Whether searched in global modules
     * @return self
     */
    public static function create(string $module, bool $isGlobal = false): self
    {
        $location = $isGlobal ? 'global' : 'app';
        $exception = new self("Module '{$module}' not found ({$location})");
        $exception->module = $module;
        $exception->isGlobal = $isGlobal;

        return $exception;
    }

    /**
     * Get module name.
     *
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * Check if global module.
     *
     * @return bool
     */
    public function isGlobal(): bool
    {
        return $this->isGlobal;
    }
}
