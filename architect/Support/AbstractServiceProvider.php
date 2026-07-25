<?php

declare(strict_types=1);

namespace Architect\Support;

use Architect\Contracts\ServiceProviderInterface;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Support\Traits\RegistersFactories;

/**
 * Base service provider with common functionality.
 */
abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    use RegistersFactories;

    /**
     * {@inheritdoc}
     */
    abstract public function register(ContainerInterface $container): void;

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container): void
    {
        // Default implementation does nothing
    }
}
