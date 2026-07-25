<?php

declare(strict_types=1);

namespace Architect\Services\Routing\Contracts;

/**
 * Interface for Router service.
 */
interface RouterInterface
{
    /**
     * Load routes from application directory.
     */
    public function loadRoutes(string $appDir): void;

    /**
     * Check if route is found.
     */
    public function hasRoute(): bool;

    /**
     * Get URL segment by index.
     */
    public function segment(int $index, string $default = ''): string;

    /**
     * Get module name.
     */
    public function getModule(): string;

    /**
     * Get controller name.
     */
    public function getController(): string;

    /**
     * Get action name.
     */
    public function getAction(): string;

    /**
     * Get parameter by name.
     */
    public function getParam(string $name, string $default = ''): string;

    /**
     * Get current path.
     */
    public function getPath(): string;
}
