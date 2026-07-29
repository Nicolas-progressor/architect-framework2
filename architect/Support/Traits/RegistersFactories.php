<?php

declare(strict_types=1);

namespace Architect\Support\Traits;

use Architect\Contracts\Core\ContainerInterface;
use Closure;

/**
 * Trait with helper methods for registering services in a container.
 */
trait RegistersFactories
{
    /**
     * Register a factory closure for a given identifier.
     */
    protected function registerFactory(
        ContainerInterface $container,
        string $id,
        Closure $factory
    ): void {
        $container->factory($id, $factory);
    }

    /**
     * Register an alias from an interface to a concrete service identifier.
     */
    protected function registerAlias(
        ContainerInterface $container,
        string $interface,
        string $concreteId
    ): void {
        $container->factory($interface, fn($c) => $c->get($concreteId));
    }

    /**
     * Register a singleton (shared) factory.
     */
    protected function registerSingleton(
        ContainerInterface $container,
        string $id,
        Closure $factory
    ): void {
        $container->singleton($id, $factory);
    }

    /**
     * Register multiple configuration variants.
     *
     * @param array<string, string> $configs Map of suffix => config file name
     */
    protected function registerConfigVariants(
        ContainerInterface $container,
        string $prefix,
        array $configs
    ): void {
        foreach ($configs as $suffix => $configName) {
            $container->factory(
                $prefix . '.' . $suffix,
                fn($c) => $c->get('config.loader')->load($configName)
            );
        }
    }
}
