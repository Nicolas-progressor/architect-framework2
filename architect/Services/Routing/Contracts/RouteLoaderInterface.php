<?php

declare(strict_types=1);

namespace Architect\Services\Routing\Contracts;

/**
 * Interface for loading routes from storage.
 */
interface RouteLoaderInterface
{
    /**
     * Load routes from a single file.
     * @return array<string, array>
     */
    public function load(string $path): array;

    /**
     * Load routes from all JSON files in directory.
     * @return array<string, array>
     */
    public function loadDirectory(string $path): array;
}
