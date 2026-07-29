<?php

declare(strict_types=1);

namespace Architect\Services\Mvc;

use Architect\Contracts\Core\ContainerInterface;

/**
 * Base class for all models.
 *
 * Automatically receives container through constructor.
 * Extend this class to create models with container access.
 */
abstract class ModelBase
{
    /** @var ContainerInterface Dependency container */
    protected ContainerInterface $container;

    /**
     * Create model instance.
     *
     * @param ContainerInterface $container Dependency container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get service from container.
     *
     * @param string $id Service identifier
     * @return mixed
     */
    protected function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    /**
     * Check if service exists in container.
     *
     * @param string $id Service identifier
     * @return bool
     */
    protected function has(string $id): bool
    {
        return $this->container->has($id);
    }
}
