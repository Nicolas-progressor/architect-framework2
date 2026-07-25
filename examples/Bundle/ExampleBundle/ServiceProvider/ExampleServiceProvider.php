<?php

declare(strict_types=1);

namespace Examples\Bundle\ExampleBundle\ServiceProvider;

use Architect\Support\AbstractServiceProvider;
use Architect\Core\Contracts\ContainerInterface;

/**
 * Example service provider for demonstration.
 */
class ExampleServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // Register additional services
        $container->singleton('example.config', function() {
            return new \Examples\Bundle\ExampleBundle\Config\ExampleConfig();
        });

        $container->singleton('example.helper', function() {
            return new \Examples\Bundle\ExampleBundle\Helper\ExampleHelper();
        });

        // Register factory
        $container->factory('example.factory', function() use ($container) {
            return new \Examples\Bundle\ExampleBundle\Factory\ExampleFactory(
                $container->get('example.config')
            );
        });

        // Register alias
        $container->alias('example', 'example.service');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container): void
    {
        // Initialize services after registration
        if ($container->has('example.config')) {
            $config = $container->get('example.config');
            $config->load();
        }

        // Register event listeners, middleware, etc.
        $this->registerEventListeners($container);
    }

    /**
     * Register event listeners.
     */
    private function registerEventListeners(ContainerInterface $container): void
    {
        // In a real implementation, you would register event listeners
        // For example:
        // $dispatcher = $container->get('event.dispatcher');
        // $dispatcher->addListener('example.event', [$this, 'handleEvent']);
    }

    /**
     * Handle example event.
     */
    public function handleEvent($event): void
    {
        // Handle event
    }
}