<?php

declare(strict_types=1);

namespace Architect\Core;

use Architect\Contracts\BundleInterface;
use Architect\Contracts\Core\ContainerInterface;
use Architect\Contracts\Core\FrameworkInterface;

/**
 * Manages bundle registration, booting and shutdown.
 */
class BundleManager
{
    /** @var BundleInterface[] */
    private array $bundles = [];

    /** @var bool */
    private bool $booted = false;

    /**
     * Register a bundle.
     */
    public function register(BundleInterface $bundle): void
    {
        $this->bundles[$bundle->getName()] = $bundle;
    }

    /**
     * Get all registered bundles.
     *
     * @return BundleInterface[]
     */
    public function getBundles(): array
    {
        return $this->bundles;
    }

    /**
     * Get a bundle by name.
     */
    public function getBundle(string $name): ?BundleInterface
    {
        return $this->bundles[$name] ?? null;
    }

    /**
     * Check if a bundle is registered.
     */
    public function hasBundle(string $name): bool
    {
        return isset($this->bundles[$name]);
    }

    /**
     * Register all bundle services.
     */
    public function registerBundles(ContainerInterface $container): void
    {
        foreach ($this->bundles as $bundle) {
            $bundle->register($container);
        }
    }

    /**
     * Boot all bundles.
     */
    public function bootBundles(ContainerInterface $container, FrameworkInterface $framework): void
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->bundles as $bundle) {
            $bundle->boot($container, $framework);
        }

        $this->booted = true;
    }

    /**
     * Shutdown all bundles.
     */
    public function shutdownBundles(ContainerInterface $container, FrameworkInterface $framework): void
    {
        foreach ($this->bundles as $bundle) {
            $bundle->shutdown($container, $framework);
        }
    }

    /**
     * Check if bundles have been booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }
}
