<?php

declare(strict_types=1);

namespace Architect\Services\Mvc;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Services\Mvc\Contracts\ModelInterface;
use Architect\Services\Mvc\Exceptions\ModelNotFoundException;
use Architect\Services\Mvc\Resolver\ModulePathResolver;
use Architect\Support\AbstractService;

/**
 * Model service for loading model instances.
 *
 * Handles model discovery, loading, and caching across
 * both application and global modules.
 */
class Model extends AbstractService implements ModelInterface
{
    /** @var array<string, object> Loaded model instances indexed by key */
    private array $models = [];

    /** @var string Current module name */
    private string $module;

    /** @var ModulePathResolver|null Module path resolver instance */
    private ?ModulePathResolver $pathResolver = null;

    /**
     * Create Model service.
     *
     * @param ContainerInterface $container Dependency container
     * @param string $module Default module name
     */
    public function __construct(ContainerInterface $container, string $module = '')
    {
        parent::__construct($container);
        $this->module = $module;
    }

    /**
     * Set path resolver.
     *
     * @param ModulePathResolver $resolver Path resolver instance
     */
    public function setPathResolver(ModulePathResolver $resolver): void
    {
        $this->pathResolver = $resolver;
    }

    /**
     * Get path resolver instance.
     *
     * @return ModulePathResolver
     */
    private function getPathResolver(): ModulePathResolver
    {
        if ($this->pathResolver === null) {
            $this->pathResolver = $this->container->get('module.resolver');
        }
        return $this->pathResolver;
    }

    /**
     * @inheritdoc
     */
    public function setModule(string $module): void
    {
        $this->module = $module;
    }

    /**
     * @inheritdoc
     */
    public function load(string $name, $module = null): ?object
    {
        $isGlobal = false;
        if ($module === true) {
            $isGlobal = true;
            $module = $name;
        } elseif ($module === null || $module === false) {
            $module = $this->module;
        }

        $key = ($isGlobal ? 'global:' : '') . $module . '/' . $name;

        // Return cached instance if available
        if (isset($this->models[$key])) {
            return $this->models[$key];
        }

        $resolver = $this->getPathResolver();
        $modelPath = $resolver->getModelPath($module, $name, $isGlobal);

        if ($modelPath === null) {
            throw ModelNotFoundException::create($module, $name, $isGlobal);
        }

        require_once $modelPath;

        $className = $resolver->buildModelClassName($module, $name, $isGlobal);

        if ($className === null) {
            throw ModelNotFoundException::create($module, $name, $isGlobal);
        }

        $this->models[$key] = new $className($this->container);
        return $this->models[$key];
    }

    /**
     * @inheritdoc
     */
    public function create(string $name, ?string $module = null): ?object
    {
        return $this->load($name, $module);
    }

    /**
     * @inheritdoc
     */
    public function get(string $id): mixed
    {
        // Delegate to container for known services
        if (in_array($id, ['apps', 'template', 'view', 'router', 'language', 'config'], true)) {
            return $this->container->get($id);
        }

        // Return loaded model from cache
        $module = $this->module;
        return $this->models[$module . '/' . $id] ?? null;
    }

    /**
     * Check if model is loaded.
     *
     * @param string $name Model name
     * @param string|null $module Module name or null for current module
     * @param bool $isGlobal Whether to check in global modules
     * @return bool
     */
    public function has(string $name, ?string $module = null, bool $isGlobal = false): bool
    {
        if ($module === null) {
            $module = $this->module;
        }

        $key = ($isGlobal ? 'global:' : '') . $module . '/' . $name;
        return isset($this->models[$key]);
    }

    /**
     * Clear loaded models cache.
     */
    public function clear(): void
    {
        $this->models = [];
    }
}
