<?php

declare(strict_types=1);

namespace Architect\Contracts;

use Architect\Core\Contracts\ContainerInterface;

/**
 * Interface for service providers that register and boot services.
 */
interface ServiceProviderInterface
{
    /**
     * Register services into the container.
     */
    public function register(ContainerInterface $container): void;

    /**
     * Boot services after registration.
     */
    public function boot(ContainerInterface $container): void;
}
