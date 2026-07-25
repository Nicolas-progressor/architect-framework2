<?php

declare(strict_types=1);

namespace Architect\Core;

use Architect\Contracts\BundleInterface;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Core\Contracts\FrameworkInterface;
use Architect\Core\Contracts\StatementInterface;
use Architect\Core\Contracts\BootableInterface;
use Architect\Core\Exception\HttpNotFoundException;

/**
 * Framework application class.
 *
 * Manages application lifecycle and service booting.
 */
class Framework implements FrameworkInterface
{
    /** @var array<string, bool> Booted services */
    private array $bootedServices = [];

    /** @var BundleManager */
    private BundleManager $bundleManager;

    /**
     * Create framework instance.
     */
    public function __construct(
        private ContainerInterface $container,
        private StatementInterface $statement
    ) {
        $this->bundleManager = new BundleManager();
        
        $this->container->set('statement', $this->statement);
        $this->container->set('framework', $this);
        $this->container->set('bundle.manager', $this->bundleManager);
    }

    /**
     * Get the DI container.
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Get the statement manager.
     */
    public function getStatement(): StatementInterface
    {
        return $this->statement;
    }

    /**
     * Get the bundle manager.
     */
    public function getBundleManager(): BundleManager
    {
        return $this->bundleManager;
    }

    /**
     * Register a bundle.
     */
    public function registerBundle(BundleInterface $bundle): void
    {
        $this->bundleManager->register($bundle);
    }

    /**
     * Register bundles from discovery.
     */
    public function registerBundlesFromDiscovery(): void
    {
        $bundleClasses = Bundle\BundleDiscovery::loadFromCache();
        if (empty($bundleClasses)) {
            $bundleClasses = Bundle\BundleDiscovery::discover();
        }

        foreach ($bundleClasses as $bundleClass) {
            if (class_exists($bundleClass)) {
                $bundle = new $bundleClass();
                $this->registerBundle($bundle);
            }
        }
    }

    /**
     * Register all bundle services.
     */
    public function registerBundleServices(): void
    {
        $this->bundleManager->registerBundles($this->container);
    }

    /**
     * Boot all bundles.
     */
    public function bootBundles(): void
    {
        $this->bundleManager->bootBundles($this->container, $this);
    }

    /**
     * Boot a service by identifier.
     */
    public function boot(string $serviceId): void
    {
        if (isset($this->bootedServices[$serviceId])) {
            return;
        }

        $service = $this->container->get($serviceId);
        
        if ($service instanceof BootableInterface) {
            $service->boot();
        }

        $this->bootedServices[$serviceId] = true;
    }

    /**
     * Boot multiple services.
     */
    public function bootAll(array $serviceIds): void
    {
        foreach ($serviceIds as $id) {
            $this->boot($id);
        }
    }

    /**
     * Run the application.
     */
    public function run(): void
    {
        try {
            $this->statement->runAll();
        } catch (HttpNotFoundException $e) {
            // 404 already handled
            return;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get a service from container.
     */
    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }
}
