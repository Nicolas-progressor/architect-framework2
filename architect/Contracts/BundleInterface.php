<?php

declare(strict_types=1);

namespace Architect\Contracts;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Core\Contracts\FrameworkInterface;

/**
 * Interface for bundles that extend the framework functionality.
 */
interface BundleInterface
{
    /**
     * Get the bundle name.
     */
    public function getName(): string;

    /**
     * Register services into the container.
     */
    public function register(ContainerInterface $container): void;

    /**
     * Boot the bundle after registration.
     */
    public function boot(ContainerInterface $container, FrameworkInterface $framework): void;

    /**
     * Shutdown the bundle.
     */
    public function shutdown(ContainerInterface $container, FrameworkInterface $framework): void;
}
