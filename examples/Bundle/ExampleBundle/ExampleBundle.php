<?php

declare(strict_types=1);

namespace Examples\Bundle\ExampleBundle;

use Architect\Support\AbstractBundle;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Core\Contracts\FrameworkInterface;

/**
 * Example bundle for demonstrating bundle system functionality.
 */
class ExampleBundle extends AbstractBundle
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Example';
    }

    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // Register bundle services
        $container->singleton('example.service', function() {
            return new \Examples\Bundle\ExampleBundle\Service\ExampleService();
        });

        $container->singleton('example.repository', function() {
            return new \Examples\Bundle\ExampleBundle\Repository\ExampleRepository();
        });

        // Register service provider
        $container->singleton('example.service_provider', function() {
            return new \Examples\Bundle\ExampleBundle\ServiceProvider\ExampleServiceProvider();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container, FrameworkInterface $framework): void
    {
        // Initialize bundle services
        $exampleService = $container->get('example.service');
        $exampleService->initialize();

        // Register bundle routes
        $this->registerRoutes($container);

        // Register bundle views
        $this->registerViews($container);
    }

    /**
     * Register bundle routes.
     */
    private function registerRoutes(ContainerInterface $container): void
    {
        if ($container->has('router')) {
            $router = $container->get('router');
            
            // Add bundle routes to router
            $routes = $this->loadRoutes();
            foreach ($routes as $route) {
                // In a real implementation, you would add routes to the router
                // $router->addRoute($route);
            }
        }
    }

    /**
     * Register bundle views.
     */
    private function registerViews(ContainerInterface $container): void
    {
        if ($container->has('template')) {
            $templateService = $container->get('template');
            
            // Add bundle view paths
            $viewPaths = $this->getViewPaths();
            foreach ($viewPaths as $namespace => $path) {
                if (method_exists($templateService, 'addNamespace')) {
                    $templateService->addNamespace($namespace, $path);
                }
            }
        }
    }

    /**
     * Load bundle routes.
     */
    private function loadRoutes(): array
    {
        return [
            [
                'path' => '/example',
                'controller' => 'example',
                'action' => 'index',
                'methods' => ['GET'],
                'name' => 'example.index'
            ],
            [
                'path' => '/example/{id}',
                'controller' => 'example',
                'action' => 'show',
                'methods' => ['GET'],
                'name' => 'example.show'
            ],
        ];
    }

    /**
     * Get bundle view paths.
     */
    private function getViewPaths(): array
    {
        $bundleDir = dirname(__DIR__);
        
        return [
            'example' => $bundleDir . '/Resources/views',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(ContainerInterface $container, FrameworkInterface $framework): void
    {
        // Cleanup bundle resources
        if ($container->has('example.service')) {
            $service = $container->get('example.service');
            $service->cleanup();
        }
    }
}