<?php

declare(strict_types=1);

namespace Architect\Core;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Core\Exception\ContainerException;
use Architect\Core\Exception\NotFoundException;
use ReflectionClass;

/**
 * Dependency Injection Container.
 * 
 * Provides service registration, resolution, and lifecycle management.
 * Created once in bootstrap and passed through DI.
 */
class Container implements ContainerInterface
{
    /** @var array<string, string|callable> Class bindings */
    private array $bindings = [];

    /** @var array<string, mixed> Resolved instances */
    private array $instances = [];

    /** @var array<string, callable> Factory callbacks */
    private array $factories = [];

    /** @var array<string, callable[]> Post-resolution callbacks */
    private array $afterResolvingCallbacks = [];

    /** @var array<string, LazyServiceWrapper> Lazy service wrappers */
    private array $lazyWrappers = [];

    /**
     * Set a service instance directly.
     */
    public function set(string $id, mixed $concrete): void
    {
        $this->instances[$id] = $concrete;
    }

    /**
     * Get a service by identifier.
     *
     * Resolution order:
     * 1. Existing instance
     * 2. Lazy service wrapper
     * 3. Factory callback
     * 4. Class binding
     *
     * @throws NotFoundException If service not found
     * @throws ContainerException If resolution fails
     */
    public function get(string $id): mixed
    {
        // Return existing instance
        if (isset($this->instances[$id])) {
            $instance = $this->instances[$id];
            $this->callAfterResolving($id, $instance);
            return $instance;
        }

        // Return lazy service wrapper
        if (isset($this->lazyWrappers[$id])) {
            $wrapper = $this->lazyWrappers[$id];
            $instance = $wrapper->getInstance();
            $this->callAfterResolving($id, $instance);
            return $instance;
        }

        // Create from factory
        if (isset($this->factories[$id])) {
            return $this->resolveAndStore($id, $this->factories[$id]);
        }

        // Create from binding
        if (isset($this->bindings[$id])) {
            $concrete = $this->bindings[$id];
            if (is_callable($concrete)) {
                return $this->resolveAndStore($id, $concrete);
            }
            if (is_string($concrete) && class_exists($concrete)) {
                return $this->resolveAndStore($id, fn() => $this->buildClass($concrete));
            }
        }

        throw new NotFoundException("Service not found: {$id}");
    }

    /**
     * Check if service is registered.
     */
    public function has(string $id): bool
    {
        return isset($this->instances[$id])
            || isset($this->lazyWrappers[$id])
            || isset($this->factories[$id])
            || isset($this->bindings[$id]);
    }

    /**
     * Check if a lazy service has been initialized.
     */
    public function isLazyInitialized(string $id): bool
    {
        if (!isset($this->lazyWrappers[$id])) {
            return false;
        }

        return $this->lazyWrappers[$id]->isInitialized();
    }

    /**
     * Register a factory for lazy service creation.
     */
    public function factory(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    /**
     * Register a binding (class name or callable) for service.
     */
    public function bind(string $id, string|callable $concrete): void
    {
        $this->bindings[$id] = $concrete;
    }

    /**
     * Register a singleton service.
     *
     * @param string $id Service identifier
     * @param string|callable $concrete Class name or factory callable
     */
    public function singleton(string $id, string|callable $concrete): void
    {
        if (is_string($concrete) && class_exists($concrete)) {
            $this->factory($id, fn() => $this->buildClass($concrete));
        } else {
            $this->factory($id, $concrete);
        }
    }

    /**
     * Register a lazy service that will be created only when first accessed.
     *
     * @param string $id Service identifier
     * @param string|callable $concrete Class name or factory callable
     */
    public function lazy(string $id, string|callable $concrete): void
    {
        if (is_string($concrete) && class_exists($concrete)) {
            $factory = fn() => $this->buildClass($concrete);
        } else {
            $factory = $concrete;
        }

        $this->lazyWrappers[$id] = new LazyServiceWrapper(
            $factory,
            $this
        );
    }

    /**
     * Create an alias for a service.
     *
     * @param string $alias Alias identifier
     * @param string $target Target service identifier
     */
    public function alias(string $alias, string $target): void
    {
        $this->bind($alias, fn($c) => $c->get($target));
    }

    /**
     * Register a callback to be called after service resolution.
     */
    public function afterResolving(string $id, callable $callback): void
    {
        $this->afterResolvingCallbacks[$id][] = $callback;

        // If already resolved, call immediately
        if (isset($this->instances[$id])) {
            $callback($this->instances[$id]);
        }
    }

    /**
     * Call all after-resolving callbacks for a service.
     */
    private function callAfterResolving(string $id, mixed $instance): void
    {
        if (!isset($this->afterResolvingCallbacks[$id])) {
            return;
        }

        foreach ($this->afterResolvingCallbacks[$id] as $callback) {
            $callback($instance);
        }
    }

    /**
     * Resolve using a resolver and store the instance.
     */
    private function resolveAndStore(string $id, callable $resolver): mixed
    {
        try {
            $instance = $resolver($this);
            $this->instances[$id] = $instance;
            $this->callAfterResolving($id, $instance);
            return $instance;
        } catch (\Exception $e) {
            throw new ContainerException(
                "Failed to resolve service '{$id}': " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Build a class instance with automatic dependency injection.
     */
    private function buildClass(string $className): object
    {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        
        if ($constructor === null) {
            return new $className();
        }
        
        $parameters = $constructor->getParameters();
        $args = [];
        foreach ($parameters as $param) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                $args[] = $this->get($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new ContainerException(
                    "Cannot resolve parameter '{$param->getName()}' for class {$className}"
                );
            }
        }
        
        return $reflection->newInstanceArgs($args);
    }

    /**
     * Reset all instances (keep bindings and factories).
     */
    public function reset(): void
    {
        $this->instances = [];
        
        // Reset all lazy wrappers
        foreach ($this->lazyWrappers as $wrapper) {
            $wrapper->reset();
        }
    }
}
