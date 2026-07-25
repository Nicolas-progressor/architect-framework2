<?php

declare(strict_types=1);

namespace Architect\Core;

use Closure;

/**
 * Lazy service wrapper for deferred service initialization.
 *
 * This wrapper delays the creation of a service until it's actually needed,
 * reducing memory usage and startup time for services that may not be used
 * during a particular request.
 */
class LazyServiceWrapper
{
    private mixed $service = null;
    private bool $initialized = false;

    public function __construct(
        private Closure $factory,
        private ?Container $container = null
    ) {}

    /**
     * Get the actual service instance, creating it if necessary.
     */
    public function getInstance(): mixed
    {
        if (!$this->initialized) {
            $this->service = ($this->factory)($this->container);
            $this->initialized = true;
        }

        return $this->service;
    }

    /**
     * Check if the service has been initialized.
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Reset the wrapper (useful for testing).
     */
    public function reset(): void
    {
        $this->service = null;
        $this->initialized = false;
    }

    /**
     * Magic method to proxy method calls to the actual service.
     */
    public function __call(string $method, array $arguments): mixed
    {
        $instance = $this->getInstance();

        if (!method_exists($instance, $method)) {
            throw new \BadMethodCallException(
                sprintf('Method %s does not exist on service', $method)
            );
        }

        return $instance->$method(...$arguments);
    }

    /**
     * Magic method to proxy property access to the actual service.
     */
    public function __get(string $property): mixed
    {
        $instance = $this->getInstance();

        if (!property_exists($instance, $property)) {
            throw new \RuntimeException(
                sprintf('Property %s does not exist on service', $property)
            );
        }

        return $instance->$property;
    }

    /**
     * Magic method to proxy property assignment to the actual service.
     */
    public function __set(string $property, mixed $value): void
    {
        $instance = $this->getInstance();

        if (!property_exists($instance, $property)) {
            throw new \RuntimeException(
                sprintf('Property %s does not exist on service', $property)
            );
        }

        $instance->$property = $value;
    }

    /**
     * Magic method to check if property exists on the actual service.
     */
    public function __isset(string $property): bool
    {
        $instance = $this->getInstance();
        return isset($instance->$property);
    }
}
