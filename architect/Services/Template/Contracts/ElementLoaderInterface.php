<?php

declare(strict_types=1);

namespace Architect\Services\Template\Contracts;

/**
 * Interface for loading template elements.
 */
interface ElementLoaderInterface
{
    /**
     * Load elements from template directory.
     * Reads elements.json file.
     */
    public function load(string $templatePath): array;

    /**
     * Load routed elements based on current route.
     * Reads all JSON files from elements/ directory and filters by route.
     */
    public function loadRouted(
        string $templatePath,
        string $module,
        string $controller,
        string $action
    ): array;
}
