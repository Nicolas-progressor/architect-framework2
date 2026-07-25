<?php

declare(strict_types=1);

namespace Architect\Helpers\Core;

use Architect\Helpers\Core\Contracts\HelperInterface;

/**
 * Abstract base class for helper services.
 * Provides default implementations for HelperInterface methods.
 */
abstract class AbstractHelper implements HelperInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getAlias(): string
    {
        // By default, use the class name without namespace and without "Helper_" prefix
        $className = static::class;
        $shortName = substr(strrchr($className, '\\'), 1);
        if (str_starts_with($shortName, 'Helper_')) {
            $shortName = substr($shortName, 7);
        }
        // Remove "Helper" suffix if present
        if (str_ends_with($shortName, 'Helper')) {
            $shortName = substr($shortName, 0, -6);
        }
        return strtolower($shortName);
    }

    /**
     * {@inheritdoc}
     */
    public static function getServiceClass(): ?string
    {
        // Default to the class itself
        return static::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function getFacadeClass(): ?string
    {
        static $facadeClassCache = [];
        $className = static::class;
        if (array_key_exists($className, $facadeClassCache)) {
            return $facadeClassCache[$className];
        }
        // Determine facade class based on naming convention
        // Pattern: Architect\Helpers\{HelperName}\{HelperName}Helper
        // Facade:   Architect\Helpers\{HelperName}\Facades\Helper_{HelperName}
        $namespaceParts = explode('\\', $className);
        $lastPart = array_pop($namespaceParts); // e.g., HtmlHelper
        $helperName = preg_replace('/Helper$/', '', $lastPart); // Html
        $baseNamespace = implode('\\', $namespaceParts); // Architect\Helpers\Html
        // Build facade class
        $facadeClass = $baseNamespace . '\\Facades\\Helper_' . $helperName;
        if (class_exists($facadeClass)) {
            $facadeClassCache[$className] = $facadeClass;
            return $facadeClass;
        }
        // Fallback: try with old pattern (Services -> Facades)
        $facadeClass = str_replace('\\Services\\', '\\Facades\\', $className);
        if (class_exists($facadeClass)) {
            $facadeClassCache[$className] = $facadeClass;
            return $facadeClass;
        }
        $facadeClassCache[$className] = null;
        return null;
    }

    /**
     * @var array<string, array<string, string>> Cache of methods per class
     */
    private static array $methodsCache = [];

    /**
     * {@inheritdoc}
     */
    public static function getMethods(): array
    {
        $serviceClass = static::getServiceClass();
        if ($serviceClass === null) {
            return [];
        }
        if (isset(self::$methodsCache[$serviceClass])) {
            return self::$methodsCache[$serviceClass];
        }
        $methods = [];
        try {
            $reflection = new \ReflectionClass($serviceClass);
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if (!$method->isStatic() && !$method->isConstructor() && !$method->isDestructor()) {
                    $methods[$method->getName()] = $method->getDocComment() ?: '';
                }
            }
        } catch (\ReflectionException) {
            // Ignore
        }
        self::$methodsCache[$serviceClass] = $methods;
        return $methods;
    }
}