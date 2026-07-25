<?php

declare(strict_types=1);

namespace Architect\Services\Blueprint\Config;

use Architect\Core\Container;

/**
 * Loads Blueprint configuration with cascade: defaults -> global -> app
 */
final class ConfigLoader
{
    private Container $container;
    private string $rootDir;

    public function __construct(Container $container, ?string $rootDir = null)
    {
        $this->container = $container;
        $this->rootDir = $rootDir ?? $this->detectRootDir();
    }

    /**
     * Load configuration with cascade
     */
    public function load(): BlueprintConfig
    {
        $paths = $this->getConfigPaths();
        return BlueprintConfig::fromPaths($paths, $this->rootDir);
    }

    /**
     * Get configuration file paths in priority order
     */
    private function getConfigPaths(): array
    {
        $paths = [];
        
        // Global config
        $globalPath = $this->getGlobalConfigPath();
        if ($globalPath) {
            $paths[] = $globalPath;
        }
        
        // App-specific config
        $appPath = $this->getAppConfigPath();
        if ($appPath) {
            $paths[] = $appPath;
        }
        
        return $paths;
    }

    /**
     * Get global config path
     */
    private function getGlobalConfigPath(): ?string
    {
        $path = defined('APP_DIR')
            ? APP_DIR . 'config/blueprint.json'
            : $this->rootDir . '/app/config/blueprint.json';
        
        return file_exists($path) ? $path : null;
    }

    /**
     * Get app-specific config path
     */
    private function getAppConfigPath(): ?string
    {
        if (!$this->container->has('apps')) {
            return null;
        }
        
        $apps = $this->container->get('apps');
        $appDir = $apps->appdir ?? null;
        
        if (!$appDir) {
            return null;
        }
        
        $path = $appDir . 'config/blueprint.json';
        
        return file_exists($path) ? $path : null;
    }

    /**
     * Detect root directory
     */
    private function detectRootDir(): string
    {
        if (defined('ROOT_DIR')) {
            return ROOT_DIR;
        }
        
        return dirname(__DIR__, 4);
    }
}
