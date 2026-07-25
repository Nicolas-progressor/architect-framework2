<?php

declare(strict_types=1);

namespace Architect\Services\Routing;

use Architect\Services\App\Contracts\AppsServiceInterface;
use Architect\Services\Routing\Contracts\FileSystemInterface;
use Architect\Services\Routing\Filesystem\NativeFileSystem;

/**
 * Resolve module and controller paths with app/global priority.
 */
final class ModuleResolver
{
    private AppsServiceInterface $apps;
    private FileSystemInterface $fs;

    public function __construct(AppsServiceInterface $apps, ?FileSystemInterface $fs = null)
    {
        $this->apps = $apps;
        $this->fs = $fs ?? new NativeFileSystem();
    }

    /**
     * Find file in app or global modules.
     * Priority: app → global.
     */
    public function findFile(string $relativePath): ?string
    {
        $appPath = $this->apps->getAppDir() . $relativePath;
        if ($this->fs->exists($appPath)) {
            return $appPath;
        }

        $globalPath = APP_DIR . $relativePath;
        if ($this->fs->exists($globalPath)) {
            return $globalPath;
        }

        return null;
    }

    /**
     * Check if module exists.
     */
    public function moduleExists(string $module): bool
    {
        return $this->findFile("modules/{$module}/controller.php") !== null
            || $this->findFile("modules/{$module}/controller/{$module}.php") !== null;
    }

    /**
     * Check if controller exists in module.
     */
    public function controllerExists(string $module, string $controller): bool
    {
        return $this->findFile("modules/{$module}/controller/{$controller}.php") !== null;
    }

    /**
     * Check if module directory exists.
     */
    public function moduleDirExists(string $module): bool
    {
        $appPath = $this->apps->getAppDir() . "modules/{$module}/";
        if ($this->fs->isDir($appPath)) {
            return true;
        }

        $globalPath = APP_DIR . "modules/{$module}/";
        return $this->fs->isDir($globalPath);
    }

    /**
     * Check if module has controller directory.
     */
    public function hasControllerDir(string $module): bool
    {
        return $this->findFile("modules/{$module}/controller") !== null
            || $this->fs->isDir($this->apps->getAppDir() . "modules/{$module}/controller/")
            || $this->fs->isDir(APP_DIR . "modules/{$module}/controller/");
    }
}
