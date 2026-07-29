<?php

declare(strict_types=1);

namespace Architect\Console;

use Architect\Contracts\Core\ContainerInterface;
use Architect\Contracts\ServiceProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Service provider for Console component
 *
 * Provides DI integration for Console services within the main application.
 * Note: Console is primarily designed for CLI usage, but can be used
 * programmatically via this provider.
 */
class ConsoleServiceProvider implements ServiceProviderInterface
{
    protected ?ContainerInterface $container = null;

    /**
     * Create service provider
     */
    public function __construct(?ContainerInterface $container = null)
    {
        if ($container !== null) {
            $this->container = $container;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // Use the container passed to register if not already set
        if ($this->container === null) {
            $this->container = $container;
        }

        // Register ConsoleFactory for creating configured console instances
        $this->container->factory('console.factory', function ($container) {
            return new ConsoleFactory();
        });

        // Register CommandRegistry separately for direct access (must be before console)
        $this->container->factory('console.registry', function ($container) {
            $logger = null;

            if ($container->has('logger')) {
                $logger = $container->get('logger');
            } elseif ($container->has(LoggerInterface::class)) {
                $logger = $container->get(LoggerInterface::class);
            }

            return new CommandRegistry(null, $logger);
        });

        // Register ConsoleKernel (singleton pattern via set for same instance)
        $this->container->set('console', $this->createConsoleKernel());
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container): void
    {
        // Ensure container is set
        if ($this->container === null) {
            $this->container = $container;
        }

        // Console is bootstrapped via CLI, not automatically during web requests
    }

    /**
     * Create ConsoleKernel instance
     */
    protected function createConsoleKernel(): ConsoleKernel
    {
        $logger = null;

        // Try to get logger from container if available
        if ($this->container->has('logger')) {
            $logger = $this->container->get('logger');
        } elseif ($this->container->has(LoggerInterface::class)) {
            $logger = $this->container->get(LoggerInterface::class);
        }

        // Use the registry already registered in container
        $registry = $this->container->get('console.registry');
        $factory = new ConsoleFactory($registry);
        $factory->setAutoDiscoverCommands(true);

        if ($logger) {
            $registry->setLogger($logger);
        }

        return $factory->create();
    }

    /**
     * Register console commands
     *
     * @param array<int, CommandInterface> $commands
     */
    public function registerCommands(array $commands): void
    {
        $console = $this->container->get('console');
        $console->registerCommands($commands);
    }

    /**
     * Get console kernel
     */
    public function getConsole(): ConsoleKernel
    {
        return $this->container->get('console');
    }

    /**
     * Get console factory for custom configuration
     */
    public function getFactory(): ConsoleFactory
    {
        return $this->container->get('console.factory');
    }

    /**
     * Get command registry
     */
    public function getRegistry(): CommandRegistry
    {
        return $this->container->get('console.registry');
    }
}
