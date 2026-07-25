<?php

declare(strict_types=1);

namespace Architect\Services\Mvc\Middleware;

use Architect\Services\Mvc\Middleware\Contracts\MiddlewareInterface;
use Psr\Container\ContainerInterface;

/**
 * Base Middleware.
 *
 * Provides common functionality for middleware.
 *
 * @package Architect\Services\Mvc\Middleware
 */
abstract class BaseMiddleware implements MiddlewareInterface
{
    /** @var ContainerInterface|null Container instance */
    protected ?ContainerInterface $container = null;

    /** @var array<string> Actions to apply middleware to (empty = all) */
    protected array $only = [];

    /** @var array<string> Actions to exclude from middleware */
    protected array $except = [];

    /**
     * Set container.
     *
     * @param ContainerInterface $container Container instance
     * @return self
     */
    public function setContainer(ContainerInterface $container): self
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Set actions to apply middleware to.
     *
     * @param array<string> $actions Action names
     * @return self
     */
    public function setOnly(array $actions): self
    {
        $this->only = $actions;
        return $this;
    }

    /**
     * Set actions to exclude from middleware.
     *
     * @param array<string> $actions Action names
     * @return self
     */
    public function setExcept(array $actions): self
    {
        $this->except = $actions;
        return $this;
    }

    /**
     * Check if middleware should run for given action.
     *
     * @param string $action Action name
     * @return bool
     */
    public function shouldRun(string $action): bool
    {
        // If "only" is set, run only for specified actions
        if (!empty($this->only)) {
            return in_array($action, $this->only, true);
        }

        // If "except" is set, run for all except specified
        if (!empty($this->except)) {
            return !in_array($action, $this->except, true);
        }

        // Run for all actions
        return true;
    }

    /**
     * Get service from container.
     *
     * @template T
     * @param string $id Service ID
     * @return T
     */
    protected function get(string $id): mixed
    {
        if ($this->container === null) {
            throw new \RuntimeException('Container not set in middleware');
        }

        return $this->container->get($id);
    }
}
