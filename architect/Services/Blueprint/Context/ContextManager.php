<?php

declare(strict_types=1);

namespace Architect\Services\Blueprint\Context;

use Architect\Core\Container;
use Architect\Services\Blueprint\Contracts\ContextManagerInterface;

/**
 * Manages template context (app, template, module)
 */
final class ContextManager implements ContextManagerInterface
{
    private ?string $currentAppDir = null;
    private ?string $currentTemplate = null;
    private ?string $rootDir = null;
    private array $paths = [];
    private ?Container $container = null;

    public function __construct(?string $rootDir = null, ?Container $container = null)
    {
        $this->rootDir = $rootDir ?? $this->detectRootDir();
        $this->container = $container;
    }

    /**
     * Set container instance
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * Get container instance
     */
    public function getContainer(): ?Container
    {
        return $this->container;
    }

    public function setContext(string $appDir, ?string $templateName = null): void
    {
        $this->currentAppDir = rtrim($appDir, '/') . '/';
        $this->currentTemplate = $templateName;
        $this->updatePaths();
    }

    public function setModuleContext(string $modulePath): void
    {
        $path = rtrim($modulePath, '/') . '/';
        $this->addPathIfExists($path . 'view/');
        $this->addPathIfExists($path . 'view/elements/');
    }

    public function getCurrentAppDir(): ?string
    {
        return $this->currentAppDir;
    }

    public function getCurrentTemplate(): ?string
    {
        return $this->currentTemplate;
    }

    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * Clear all paths
     */
    public function clearPaths(): void
    {
        $this->paths = [];
    }

    /**
     * Update paths based on current context
     */
    private function updatePaths(): void
    {
        $this->paths = [];

        // Template-specific paths (highest priority)
        if ($this->currentTemplate && $this->currentAppDir) {
            $this->addTemplatePaths($this->currentAppDir, $this->currentTemplate);
        }

        // App template paths
        if ($this->currentAppDir) {
            $this->addAppPaths($this->currentAppDir);
        }

        // Global template paths
        $this->addGlobalPaths();
    }

    /**
     * Add template-specific paths
     */
    private function addTemplatePaths(string $appDir, string $template): void
    {
        $basePath = $appDir . 'template/' . $template . '/';

        $this->addPathIfExists($basePath);
        $this->addPathIfExists($basePath . 'layouts/');
        $this->addPathIfExists($basePath . 'elements/');
    }

    /**
     * Add app-level paths
     */
    private function addAppPaths(string $appDir): void
    {
        $this->addPathIfExists($appDir . 'template/');
        $this->addPathIfExists($appDir . 'template/layouts/');
        $this->addPathIfExists($appDir . 'template/elements/');
    }

    /**
     * Add global paths
     */
    private function addGlobalPaths(): void
    {
        $globalBase = $this->rootDir . '/app/template/';

        $this->addPathIfExists($globalBase);
        $this->addPathIfExists($globalBase . 'layouts/');
        $this->addPathIfExists($globalBase . 'elements/');
    }

    /**
     * Add path if directory exists
     */
    private function addPathIfExists(string $path): void
    {
        $normalizedPath = rtrim($path, '/') . '/';

        if (is_dir($normalizedPath) && !in_array($normalizedPath, $this->paths, true)) {
            $this->paths[] = $normalizedPath;
        }
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
