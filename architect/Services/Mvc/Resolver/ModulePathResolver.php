<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Resolver;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Services\Mvc\Contracts\ModuleResolverInterface;

/**
 * Module path resolver.
 *
 * Centralizes all module path resolution logic for controllers,
 * models, views, and bootstrap files. Supports both application
 * modules and global modules.
 *
 * @package Architect\Services\Mvc\Resolver
 */
class ModulePathResolver implements ModuleResolverInterface
{
    /** @var ContainerInterface Dependency container */
    private ContainerInterface $container;

    /** @var ClassNameResolver Class name resolver */
    private ClassNameResolver $classNameResolver;

    /** @var string|null Cached application directory */
    private ?string $appDir = null;

    /**
     * Create resolver instance.
     *
     * @param ContainerInterface $container Dependency container
     * @param ClassNameResolver|null $classNameResolver Class name resolver
     */
    public function __construct(
        ContainerInterface $container,
        ?ClassNameResolver $classNameResolver = null
    ) {
        $this->container = $container;
        $this->classNameResolver = $classNameResolver ?? new ClassNameResolver();
    }

    /**
     * @inheritdoc
     */
    public function resolvePath(string $module, string $type, bool $isGlobal = false): string
    {
        $base = $this->getBasePath($isGlobal);
        return rtrim($base, '/') . "/modules/{$module}/{$type}/";
    }

    /**
     * @inheritdoc
     */
    public function moduleExists(string $module, bool $isGlobal = false): bool
    {
        $path = $this->resolvePath($module, '', $isGlobal);
        return is_dir($path);
    }

    /**
     * @inheritdoc
     */
    public function isGlobalModule(string $module): bool
    {
        // Check if module exists in app modules
        $appPath = $this->resolvePath($module, '', false);
        if (is_dir($appPath)) {
            return false;
        }

        // Check if module exists in global modules
        $globalPath = $this->resolvePath($module, '', true);
        return is_dir($globalPath);
    }

    /**
     * @inheritdoc
     */
    public function getControllerPath(string $module, string $controller, bool $isGlobal = false): ?string
    {
        // Try controller directory first
        $controllerDir = $this->resolvePath($module, 'controller', $isGlobal);
        $controllerFile = $controllerDir . "{$controller}.php";

        if (file_exists($controllerFile)) {
            return $controllerFile;
        }

        // Try single controller file
        $singleController = $this->resolvePath($module, '', $isGlobal) . 'controller.php';
        if (file_exists($singleController)) {
            return $singleController;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getModelPath(string $module, string $model, bool $isGlobal = false): ?string
    {
        $modelDir = $this->resolvePath($module, 'model', $isGlobal);
        $modelFile = $modelDir . "{$model}.php";

        return file_exists($modelFile) ? $modelFile : null;
    }

    /**
     * @inheritdoc
     */
    public function getViewPath(string $module, bool $isGlobal = false): string
    {
        return $this->resolvePath($module, 'view', $isGlobal);
    }

    /**
     * Get module base path (without type).
     *
     * @param string $module Module name
     * @param bool $isGlobal Whether to get global module path
     * @return string
     */
    public function getModuleBasePath(string $module, bool $isGlobal = false): string
    {
        $base = $this->getBasePath($isGlobal);
        return rtrim($base, '/') . "/modules/{$module}/";
    }

    /**
     * Get module bootstrap path.
     *
     * @param string $module Module name
     * @param bool $isGlobal Whether to check global modules
     * @return string|null Bootstrap path or null if not found
     */
    public function getModuleBootstrapPath(string $module, bool $isGlobal = false): ?string
    {
        $basePath = $this->getModuleBasePath($module, $isGlobal);
        $bootstrapFile = $basePath . 'modulebootstrap.php';

        return file_exists($bootstrapFile) ? $bootstrapFile : null;
    }

    /**
     * Build controller class name.
     *
     * @param string $module Module name
     * @param string $controller Controller name
     * @param bool $isGlobal Whether module is global
     * @param bool $isControllerDir Whether controller is in directory
     * @return string|null Class name or null if not found
     */
    public function buildControllerClassName(
        string $module,
        string $controller,
        bool $isGlobal,
        bool $isControllerDir = true
    ): ?string {
        $appName = $this->getCurrentAppName();
        $variants = $this->classNameResolver->buildControllerVariants(
            $module,
            $controller,
            $appName,
            $isGlobal,
            $isControllerDir
        );

        return $this->classNameResolver->resolveFromVariants($variants);
    }

    /**
     * Build model class name.
     *
     * @param string $module Module name
     * @param string $model Model name
     * @param bool $isGlobal Whether module is global
     * @return string|null Class name or null if not found
     */
    public function buildModelClassName(string $module, string $model, bool $isGlobal): ?string
    {
        $appName = $this->getCurrentAppName();
        $variants = $this->classNameResolver->buildModelVariants(
            $module,
            $model,
            $appName,
            $isGlobal
        );

        return $this->classNameResolver->resolveFromVariants($variants);
    }

    /**
     * Build bootstrap class name.
     *
     * @param string $module Module name
     * @param bool $isGlobal Whether module is global
     * @return string|null Class name or null if not found
     */
    public function buildBootstrapClassName(string $module, bool $isGlobal): ?string
    {
        $appName = $this->getCurrentAppName();
        $variants = $this->classNameResolver->buildBootstrapVariants(
            $module,
            $appName,
            $isGlobal
        );

        return $this->classNameResolver->resolveFromVariants($variants);
    }

    /**
     * Build cache key for component.
     *
     * @param string $module Module name
     * @param string|null $component Component name
     * @param bool $isGlobal Whether module is global
     * @return string
     */
    public function buildCacheKey(string $module, ?string $component, bool $isGlobal): string
    {
        return $this->classNameResolver->buildCacheKey($module, $component, $isGlobal);
    }

    /**
     * Get base path for modules.
     *
     * @param bool $isGlobal Whether to get global modules path
     * @return string
     */
    private function getBasePath(bool $isGlobal): string
    {
        if ($isGlobal) {
            return APP_DIR;
        }

        return $this->getAppDir();
    }

    /**
     * Get current application directory.
     *
     * @return string
     */
    private function getAppDir(): string
    {
        if ($this->appDir === null) {
            $apps = $this->container->get('apps');
            $this->appDir = $apps->getAppDir();
        }

        return $this->appDir;
    }

    /**
     * Get current application name.
     *
     * @return string
     */
    private function getCurrentAppName(): string
    {
        $apps = $this->container->get('apps');
        return str_replace('-', '', $apps->getCurrentApp());
    }

    /**
     * Reset cached application directory.
     *
     * Call this when switching applications.
     */
    public function resetCache(): void
    {
        $this->appDir = null;
    }
}
