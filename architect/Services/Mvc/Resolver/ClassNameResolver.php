<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Resolver;

/**
 * Class name resolver for MVC components.
 *
 * Centralizes class name resolution logic to avoid duplication
 * across loaders and resolvers.
 *
 * @package Architect\Services\Mvc\Resolver
 */
class ClassNameResolver
{
    /**
     * Build controller class name variants.
     *
     * @param string $module Module name
     * @param string $controller Controller name
     * @param string $appName Application name (normalized)
     * @param bool $isGlobal Whether module is global
     * @param bool $isControllerDir Whether controller is in directory
     * @return array<string> List of class name variants to try
     */
    public function buildControllerVariants(
        string $module,
        string $controller,
        string $appName,
        bool $isGlobal,
        bool $isControllerDir = true
    ): array {
        if ($isGlobal) {
            return [
                "app\\modules\\{$module}\\controller\\{$controller}",
                "app\\modules\\{$module}\\controller\\" . ucfirst($controller),
                "app\\modules\\{$module}\\controller\\" . strtolower($controller),
                "{$module}\\controller\\{$controller}",
            ];
        }

        if ($isControllerDir) {
            return [
                "app\\{$appName}\\modules\\{$module}\\controller\\{$controller}",
                "app\\{$appName}\\modules\\{$module}\\controller\\" . ucfirst($controller),
                "{$module}\\controller\\{$controller}",
            ];
        }

        return [
            "app\\{$appName}\\modules\\{$module}\\controller\\{$module}",
            "app\\{$appName}\\modules\\{$module}\\controller",
            "{$module}\\controller",
        ];
    }

    /**
     * Build model class name variants.
     *
     * @param string $module Module name
     * @param string $model Model name
     * @param string $appName Application name (normalized)
     * @param bool $isGlobal Whether module is global
     * @return array<string> List of class name variants to try
     */
    public function buildModelVariants(
        string $module,
        string $model,
        string $appName,
        bool $isGlobal
    ): array {
        if ($isGlobal) {
            return [
                "app\\modules\\{$module}\\model\\" . strtolower($model),
                "app\\modules\\{$module}\\model\\{$model}",
                "{$module}\\model\\{$model}",
            ];
        }

        return [
            "app\\{$appName}\\modules\\{$module}\\model\\" . strtolower($model),
            "app\\{$appName}\\modules\\{$module}\\model\\{$model}",
            "{$module}\\model\\{$model}",
        ];
    }

    /**
     * Build bootstrap class name variants.
     *
     * @param string $module Module name
     * @param string $appName Application name (normalized)
     * @param bool $isGlobal Whether module is global
     * @return array<string> List of class name variants to try
     */
    public function buildBootstrapVariants(
        string $module,
        string $appName,
        bool $isGlobal
    ): array {
        if ($isGlobal) {
            return [
                "app\\modules\\{$module}\\modulebootstrap",
                "{$module}\\modulebootstrap",
            ];
        }

        return [
            "app\\{$appName}\\modules\\{$module}\\modulebootstrap",
            "{$module}\\modulebootstrap",
        ];
    }

    /**
     * Resolve class name from variants.
     *
     * @param array<string> $variants Class name variants
     * @return string|null First existing class or null
     */
    public function resolveFromVariants(array $variants): ?string
    {
        foreach ($variants as $className) {
            if (class_exists($className)) {
                return $className;
            }
        }

        return null;
    }

    /**
     * Build cache key for component.
     *
     * @param string $module Module name
     * @param string|null $component Component name (controller/model)
     * @param bool $isGlobal Whether module is global
     * @return string Cache key
     */
    public function buildCacheKey(string $module, ?string $component, bool $isGlobal): string
    {
        $prefix = $isGlobal ? 'global:' : '';

        if ($component === null) {
            return "{$prefix}{$module}";
        }

        return "{$prefix}{$module}/{$component}";
    }
}
