<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Loader;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Services\Mvc\Cache\ComponentCacheTrait;
use Architect\Services\Mvc\Loader\Contracts\ModuleBootstrapLoaderInterface;
use Architect\Services\Mvc\Resolver\ModulePathResolver;

/**
 * Module bootstrap loader implementation.
 * 
 * Handles module bootstrap discovery, loading, and statement registration.
 * Supports both application and global module bootstraps.
 * 
 * @package Architect\Services\Mvc\Loader
 */
class ModuleBootstrapLoader implements ModuleBootstrapLoaderInterface
{
    use ComponentCacheTrait;

    /** @var ContainerInterface Dependency container */
    private ContainerInterface $container;

    /** @var ModulePathResolver Module path resolver */
    private ModulePathResolver $pathResolver;

    /** @var array<string> Available statement names */
    private array $statementNames = [
        'core_preinit',
        'core_init',
        'core_load',
        'core_post_load',
        'app_load',
        'app_data',
        'app_output',
        'render'
    ];

    /**
     * Create module bootstrap loader instance.
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
    public function load(string $module, bool $isGlobal = false): ?object
    {
        $key = $this->pathResolver->buildCacheKey($module, null, $isGlobal);

        // Return cached instance if available
        if ($this->hasCached($key)) {
            return $this->getCached($key);
        }

        $bootstrapPath = $this->pathResolver->getModuleBootstrapPath($module, $isGlobal);

        if ($bootstrapPath === null) {
            return null;
        }

        // Load bootstrap file
        require_once $bootstrapPath;

        // Resolve class name using path resolver
        $className = $this->pathResolver->buildBootstrapClassName($module, $isGlobal);

        if ($className === null || !class_exists($className)) {
            return null;
        }

        // Create bootstrap instance
        $instance = new $className();
        $this->setCached($key, $instance);

        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function exists(string $module, bool $isGlobal = false): bool
    {
        return $this->pathResolver->getModuleBootstrapPath($module, $isGlobal) !== null;
    }

    /**
     * @inheritdoc
     */
    public function registerStatementHandlers(object $bootstrap): void
    {
        $statement = $this->container->get('statement');
        $methods = get_class_methods($bootstrap);

        foreach ($methods as $method) {
            foreach ($this->statementNames as $statementName) {
                // Check for method_{statement} or {anything}_{statement} pattern
                if ($method === 'method_' . $statementName || str_ends_with($method, '_' . $statementName)) {
                    $statement->on($statementName, fn($c) => $bootstrap->{$method}(), 10);
                }
            }
        }
    }

    /**
     * Set available statement names.
     * 
     * @param array $names Statement names
     */
    public function setStatementNames(array $names): void
    {
        $this->statementNames = $names;
    }

    /**
     * Get available statement names.
     * 
     * @return array
     */
    public function getStatementNames(): array
    {
        return $this->statementNames;
    }
}

