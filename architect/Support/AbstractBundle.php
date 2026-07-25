<?php

declare(strict_types=1);

namespace Architect\Support;

use Architect\Contracts\BundleInterface;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Core\Contracts\FrameworkInterface;

/**
 * Base bundle class with common functionality.
 */
abstract class AbstractBundle implements BundleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        // Default implementation based on class name
        $class = (new \ReflectionClass($this))->getShortName();
        return preg_replace('/Bundle$/', '', $class);
    }

    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // Default implementation does nothing
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container, FrameworkInterface $framework): void
    {
        // Default implementation does nothing
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(ContainerInterface $container, FrameworkInterface $framework): void
    {
        // Default implementation does nothing
    }
}