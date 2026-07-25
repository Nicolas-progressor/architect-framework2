<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Middleware;

use Architect\Services\Mvc\Middleware\Contracts\MiddlewareInterface;
use Psr\Container\ContainerInterface;

/**
 * Middleware Resolver.
 *
 * Resolves middleware from class names, instances, or callables.
 *
 * @package Architect\Services\Mvc\Middleware
 */
class MiddlewareResolver
{
    /** @var ContainerInterface Container instance */
    private ContainerInterface $container;

    /** @var array<string, class-string<MiddlewareInterface>> Middleware aliases */
    private array $aliases = [];

    /**
     * Create resolver instance.
     *
     * @param ContainerInterface $container Container instance
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Resolve middleware from mixed value.
     *
     * @param mixed $middleware Middleware class name, instance, or callable
     * @return MiddlewareInterface
     * @throws \InvalidArgumentException If middleware cannot be resolved
     */
    public function resolve(mixed $middleware): MiddlewareInterface
    {
        // Already an instance
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        // Class name
        if (is_string($middleware) && class_exists($middleware)) {
            return $this->resolveFromClass($middleware);
        }

        // Alias
        if (is_string($middleware) && isset($this->aliases[$middleware])) {
            return $this->resolveFromClass($this->aliases[$middleware]);
        }

        // Callable wrapper
        if (is_callable($middleware)) {
            return $this->resolveFromCallable($middleware);
        }

        throw new \InvalidArgumentException(sprintf(
            'Cannot resolve middleware: %s',
            is_string($middleware) ? $middleware : gettype($middleware)
        ));
    }

    /**
     * Resolve middleware from class name.
     *
     * @param class-string<MiddlewareInterface> $className Class name
     * @return MiddlewareInterface
     */
    protected function resolveFromClass(string $className): MiddlewareInterface
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Middleware class not found: {$className}");
        }

        $middleware = new $className();

        if (!$middleware instanceof MiddlewareInterface) {
            throw new \InvalidArgumentException(
                "Class {$className} must implement MiddlewareInterface"
            );
        }

        // Inject container if middleware has setContainer method
        if (method_exists($middleware, 'setContainer')) {
            $middleware->setContainer($this->container);
        }

        return $middleware;
    }

    /**
     * Resolve middleware from callable.
     *
     * @param callable $callable Callable middleware
     * @return MiddlewareInterface
     */
    protected function resolveFromCallable(callable $callable): MiddlewareInterface
    {
        return new class ($callable) implements MiddlewareInterface {
            private $callable;

            public function __construct(callable $callable)
            {
                $this->callable = $callable;
            }

            public function process($request, $handler): \Psr\Http\Message\ResponseInterface
            {
                return ($this->callable)($request, $handler);
            }
        };
    }

    /**
     * Register middleware alias.
     *
     * @param string $alias Alias name
     * @param class-string<MiddlewareInterface> $className Class name
     * @return self
     */
    public function alias(string $alias, string $className): self
    {
        $this->aliases[$alias] = $className;
        return $this;
    }

    /**
     * Register multiple aliases.
     *
     * @param array<string, class-string<MiddlewareInterface>> $aliases Aliases
     * @return self
     */
    public function aliases(array $aliases): self
    {
        foreach ($aliases as $alias => $className) {
            $this->alias($alias, $className);
        }
        return $this;
    }

    /**
     * Check if alias exists.
     *
     * @param string $alias Alias name
     * @return bool
     */
    public function hasAlias(string $alias): bool
    {
        return isset($this->aliases[$alias]);
    }

    /**
     * Get class name for alias.
     *
     * @param string $alias Alias name
     * @return string|null
     */
    public function getAliasClass(string $alias): ?string
    {
        return $this->aliases[$alias] ?? null;
    }
}
