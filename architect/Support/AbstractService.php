<?php

declare(strict_types=1);

namespace Architect\Support;

use Architect\Core\Contracts\ContainerInterface;

/**
 * Base class for services.
 *
 * Provides common functionality for all services including
 * container access and boot lifecycle.
 */
abstract class AbstractService
{
    /**
     * Create service instance.
     */
    public function __construct(
        protected ContainerInterface $container
    ) {}

    /**
     * Boot the service (called after all services are registered).
     */
    public function boot(): void {}

    /**
     * Get a service from the container.
     */
    protected function get(string $id): mixed
    {
        return $this->container->get($id);
    }
}
