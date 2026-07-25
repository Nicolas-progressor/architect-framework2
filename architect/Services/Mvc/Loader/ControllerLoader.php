<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Loader;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Services\Mvc\Cache\ComponentCacheTrait;
use Architect\Services\Mvc\Contracts\ControllerInterface;
use Architect\Services\Mvc\Loader\Contracts\ControllerLoaderInterface;
use Architect\Services\Mvc\Resolver\ModulePathResolver;

/**
 * Controller loader implementation.
 * 
 * Handles controller discovery, loading, and instantiation.
 * Supports both application and global module controllers.
 * 
 * @package Architect\Services\Mvc\Loader
 */
class ControllerLoader implements ControllerLoaderInterface
{
    use ComponentCacheTrait;

    /** @var ContainerInterface Dependency container */
    private ContainerInterface $container;

    /** @var ModulePathResolver Module path resolver */
    private ModulePathResolver $pathResolver;

    /**
     * Create controller loader instance.
     * 
     * @param ContainerInterface $container Dependency container
     * @param ModulePathResolver $pathResolver Module path resolver
     */
    public function __construct(
        ContainerInterface $container,
        ModulePathResolver $pathResolver
    ) {
        $this->container = $container;
        $this->pathResolver = $pathResolver;
    }

    /**
     * @inheritdoc
     */
    public function load(string $module, string $controller, bool $isGlobal = false): ?ControllerInterface
    {
        $key = $this->pathResolver->buildCacheKey($module, $controller, $isGlobal);

        // Return cached instance if available
        if ($this->hasCached($key)) {
            return $this->getCached($key);
        }

        $controllerPath = $this->getFilePath($module, $controller, $isGlobal);

        if ($controllerPath === null) {
            return null;
        }

        // Load controller file
        require_once $controllerPath;

        // Determine if controller is in directory
        $isControllerDir = !str_ends_with($controllerPath, 'controller.php');

        // Resolve class name
        $className = $this->pathResolver->buildControllerClassName(
            $module,
            $controller,
            $isGlobal,
            $isControllerDir
        );

        if ($className === null || !class_exists($className)) {
            return null;
        }

        // Create controller instance
        $instance = new $className($this->container, $module, $isGlobal);

        if ($instance instanceof ControllerInterface) {
            $this->setCached($key, $instance);
            return $instance;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function exists(string $module, string $controller, bool $isGlobal = false): bool
    {
        return $this->getFilePath($module, $controller, $isGlobal) !== null;
    }

    /**
     * @inheritdoc
     */
    public function getFilePath(string $module, string $controller, bool $isGlobal = false): ?string
    {
        return $this->pathResolver->getControllerPath($module, $controller, $isGlobal);
    }

    /**
     * Get loaded controller by key.
     * 
     * @param string $module Module name
     * @param string $controller Controller name
     * @param bool $isGlobal Whether module is global
     * @return ControllerInterface|null
     */
    public function getLoaded(string $module, string $controller, bool $isGlobal = false): ?ControllerInterface
    {
        $key = $this->pathResolver->buildCacheKey($module, $controller, $isGlobal);
        return $this->getCached($key);
    }
}
