<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Exceptions;

/**
 * Exception thrown when controller cannot be found.
 *
 * Thrown when either the controller file or class is missing.
 * Provides module and controller information for debugging.
 *
 * @package Architect\Services\Mvc\Exceptions
 */
class ControllerNotFoundException extends MvcException
{
    /** @var string Module name */
    private string $module;

    /** @var string Controller name */
    private string $controller;

    /** @var string|null Expected file path */
    private ?string $expectedFile;

    /**
     * Create exception for missing controller file.
     *
     * @param string $module Module name
     * @param string $controller Controller name
     * @param string|null $file Expected file path
     * @return self
     */
    public static function fileNotFound(string $module, string $controller, ?string $file = null): self
    {
        $exception = new self("Controller file not found for module '{$module}': {$controller}");
        $exception->module = $module;
        $exception->controller = $controller;
        $exception->expectedFile = $file;

        return $exception;
    }

    /**
     * Create exception for missing controller class.
     *
     * @param string $module Module name
     * @param string $controller Controller name
     * @param string $class Expected class name
     * @return self
     */
    public static function classNotFound(string $module, string $controller, string $class): self
    {
        $exception = new self("Controller class not found: {$class}");
        $exception->module = $module;
        $exception->controller = $controller;
        $exception->expectedFile = null;

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
     * Get controller name.
     *
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * Get expected file path.
     *
     * @return string|null
     */
    public function getExpectedFile(): ?string
    {
        return $this->expectedFile;
    }
}
