<?php

declare(strict_types=1);

namespace Architect\Services\Config;

use Architect\Services\Routing\Contracts\FileSystemInterface;

/**
 * Resolves configuration file paths.
 * 
 * Searches for configuration files in multiple locations with priority.
 */
final class ConfigPathResolver
{
    /**
     * Create path resolver.
     * 
     * @param FileSystemInterface $fs File system abstraction
     * @param string $appDir Application directory path
     * @param string $rootDir Root directory path
     */
    public function __construct(
        private readonly FileSystemInterface $fs,
        private readonly string $appDir,
        private readonly string $rootDir
    ) {}

    /**
     * Resolve configuration file path.
     * 
     * Search priority:
     * 1. App-specific path (if provided)
     * 2. App config directory
     * 3. Root config directory
     * 
     * @param string $name Configuration name (without .json extension)
     * @param string|null $appPath Optional application-specific path
     * @return string|null Full path to config file or null if not found
     */
    public function resolve(string $name, ?string $appPath = null): ?string
    {
        $paths = $this->getSearchPaths($name, $appPath);

        foreach ($paths as $path) {
            if ($this->fs->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Get all possible configuration paths for a name.
     * 
     * @param string $name Configuration name
     * @param string|null $appPath Optional application-specific path
     * @return array<string> List of possible paths in priority order
     */
    public function getSearchPaths(string $name, ?string $appPath = null): array
    {
        $paths = [];

        // 1. App-specific path (highest priority)
        if ($appPath !== null) {
            $paths[] = rtrim($appPath, '/') . "/config/{$name}.json";
        }

        // 2. App config directory
        $paths[] = rtrim($this->appDir, '/') . "/config/{$name}.json";

        // 3. Root config directory (for shared configs)
        $paths[] = rtrim($this->rootDir, '/') . "/config/{$name}.json";

        return $paths;
    }

    /**
     * Get the application directory.
     * 
     * @return string
     */
    public function getAppDir(): string
    {
        return $this->appDir;
    }

    /**
     * Get the root directory.
     * 
     * @return string
     */
    public function getRootDir(): string
    {
        return $this->rootDir;
    }
}
