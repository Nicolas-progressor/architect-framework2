<?php

declare(strict_types=1);

namespace Architect\Services\Routing\Loaders;

use Architect\Services\Routing\Contracts\FileSystemInterface;
use Architect\Services\Routing\Contracts\RouteLoaderInterface;
use Architect\Services\Routing\Filesystem\NativeFileSystem;

/**
 * Load routes from JSON files.
 */
final class JsonRouteLoader implements RouteLoaderInterface
{
    private FileSystemInterface $fs;

    public function __construct(?FileSystemInterface $fs = null)
    {
        $this->fs = $fs ?? new NativeFileSystem();
    }

    /**
     * @inheritdoc
     */
    public function load(string $path): array
    {
        return $this->fs->json($path) ?? [];
    }

    /**
     * @inheritdoc
     */
    public function loadDirectory(string $path): array
    {
        if (!$this->fs->isDir($path)) {
            return [];
        }

        $files = $this->fs->glob($path . '*.json');

        // Сортировка: home первый
        usort($files, $this->sortFiles(...));

        $routes = [];
        foreach ($files as $file) {
            $data = $this->load($file);
            $filename = basename($file, '.json');
            $routes = $this->mergeRoutes($routes, $data, $filename);
        }

        return $routes;
    }

    /**
     * Sort files with 'home' first.
     */
    private function sortFiles(string $a, string $b): int
    {
        $aName = basename($a, '.json');
        $bName = basename($b, '.json');

        return match (true) {
            $aName === 'home' => -1,
            $bName === 'home' => 1,
            default => 0,
        };
    }

    /**
     * Merge routes with aliases.
     */
    private function mergeRoutes(array $routes, array $data, string $filename): array
    {
        if (!isset($data['routes'])) {
            return $routes;
        }

        // Add routes (don't overwrite existing)
        foreach ($data['routes'] as $key => $route) {
            if (!isset($routes[$key])) {
                $routes[$key] = $route;
            }
        }

        // Add aliases for root route
        if (isset($data['routes']['/'])) {
            $routes['index'] ??= $data['routes']['/'];
            $routes['default'] ??= $data['routes']['/'];
        }

        // Add alias by filename
        if (!isset($routes[$filename]) && isset($data['routes']['index'])) {
            $routes[$filename] = $data['routes']['index'];
        }

        return $routes;
    }

    /**
     * Load routes from a single configuration file (e.g., config/routes.json).
     * Returns processed routes array.
     */
    public function loadConfig(string $path): array
    {
        if (!$this->fs->exists($path)) {
            return [];
        }

        $data = $this->load($path);
        return $this->mergeRoutes([], $data, basename($path, '.json'));
    }
}
