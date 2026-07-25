<?php

declare(strict_types=1);

namespace Architect\Helpers\Core;

use Architect\Core\Contracts\ContainerInterface;
use RuntimeException;

/**
 * Base facade class for static access to services.
 *
 * @method static mixed getFacadeRoot()
 */
abstract class Facade
{
    /** @var ContainerInterface|null DI container */
    protected static ?ContainerInterface $container = null;

    /** @var array<string, object> Resolved service instances */
    protected static array $resolvedInstances = [];

    /**
     * Set the container instance.
     */
    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    /**
     * Check if container has been set.
     */
    public static function hasContainer(): bool
    {
        return self::$container !== null;
    }

    /**
     * Get the container instance.
     */
    public static function getContainer(): ContainerInterface
    {
        if (!self::$container) {
            throw new RuntimeException('Facade container has not been set. Please call Facade::setContainer() first.');
        }
        return self::$container;
    }

    /**
     * Get the service key that this facade represents.
     */
    abstract protected static function getFacadeAccessor(): string;

    /**
     * Get the root object behind the facade.
     */
    public static function getFacadeRoot(): object
    {
        return self::resolveFacadeInstance(static::getFacadeAccessor());
    }

    /**
     * Resolve the facade instance from the container.
     */
    protected static function resolveFacadeInstance(string $name): object
    {
        if (isset(self::$resolvedInstances[$name])) {
            return self::$resolvedInstances[$name];
        }

        $container = self::getContainer();
        $instance = $container->get($name);
        self::$resolvedInstances[$name] = $instance;
        return $instance;
    }

    /**
     * Clear all resolved instances (for testing).
     */
    public static function clearResolvedInstances(): void
    {
        self::$resolvedInstances = [];
    }

    /**
     * Clear a specific resolved instance.
     */
    public static function clearResolvedInstance(string $name): void
    {
        unset(self::$resolvedInstances[$name]);
    }

    /**
     * Handle dynamic static calls to the facade.
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = self::getFacadeRoot();

        if (!method_exists($instance, $method)) {
            throw new RuntimeException(sprintf(
                'Method %s::%s does not exist.',
                get_class($instance),
                $method
            ));
        }

        return $instance->$method(...$args);
    }
}