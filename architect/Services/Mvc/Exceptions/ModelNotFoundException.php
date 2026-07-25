<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Exceptions;

/**
 * Exception thrown when model cannot be found.
 *
 * Thrown when either the model file or class is missing.
 * Provides module, model name, and location information.
 *
 * @package Architect\Services\Mvc\Exceptions
 */
class ModelNotFoundException extends MvcException
{
    /** @var string Module name */
    private string $module;

    /** @var string Model name */
    private string $model;

    /** @var bool Whether the model was searched in global modules */
    private bool $isGlobal;

    /**
     * Create exception for missing model.
     *
     * @param string $module Module name
     * @param string $model Model name
     * @param bool $isGlobal Whether searched in global modules
     * @return self
     */
    public static function create(string $module, string $model, bool $isGlobal = false): self
    {
        $location = $isGlobal ? 'global modules' : 'app modules';
        $exception = new self("Model '{$model}' not found in module '{$module}' ({$location})");
        $exception->module = $module;
        $exception->model = $model;
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
     * Get model name.
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
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
