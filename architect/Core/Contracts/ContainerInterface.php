<?php

declare(strict_types=1);

namespace Architect\Core\Contracts;

use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Container interface for dependency injection.
 * 
 * Extends PSR-11 ContainerInterface for compatibility.
 */
interface ContainerInterface extends PsrContainerInterface
{
    /**
     * Set a service instance.
     */
    public function set(string $id, mixed $concrete): void;

    /**
     * Register a factory for lazy service creation.
     */
    public function factory(string $id, callable $factory): void;

    /**
     * Register a binding (class name) for service.
     */
    public function bind(string $id, string $concrete): void;

    /**
     * Register a callback to be called after service resolution.
     */
    public function afterResolving(string $id, callable $callback): void;

    /**
     * Reset all instances.
     */
    public function reset(): void;
}
