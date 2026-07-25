<?php

declare(strict_types=1);

namespace Architect\Support\ServiceProviders;

use Architect\Contracts\ServiceProviderInterface;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Core\Contracts\StatementInterface;
use Architect\Support\StatementConfigurator;

/**
 * Aggregate service provider that delegates to multiple providers.
 */
class AggregateServiceProvider implements ServiceProviderInterface
{
    /**
     * @var ServiceProviderInterface[]
     */
    private array $providers = [];

    /**
     * @var ContainerInterface|null
     */
    private ?ContainerInterface $container = null;

    /**
     * @param ServiceProviderInterface[] $providers
     */
    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }

    /**
     * Add a provider.
     */
    public function add(ServiceProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        $this->container = $container;
        foreach ($this->providers as $provider) {
            $provider->register($container);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container): void
    {
        $this->container = $container;
        foreach ($this->providers as $provider) {
            $provider->boot($container);
        }
    }

    /**
     * Configure statement lifecycle hooks.
     */
    public function configureStatements(StatementInterface $statement): void
    {
        if ($this->container === null) {
            throw new \RuntimeException('Container not set. Ensure register() or boot() has been called.');
        }

        $configurator = new StatementConfigurator();
        $configurator->configure($statement, $this->container);
    }
}