<?php

declare(strict_types=1);

namespace Architect\Services\Template\Contracts;

/**
 * Interface for Blueprint template engine adapter.
 */
interface BlueprintAdapterInterface
{
    /**
     * Check if Blueprint is available.
     */
    public function isAvailable(): bool;

    /**
     * Set current template context.
     */
    public function setTemplate(string $path, string $name): void;

    /**
     * Set search paths for templates.
     */
    public function setPaths(array $paths): void;

    /**
     * Render template with data.
     */
    public function render(string $template, array $data): string;
}
