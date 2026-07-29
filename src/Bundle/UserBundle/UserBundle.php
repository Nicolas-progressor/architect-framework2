<?php

declare(strict_types=1);

namespace App\Bundle\UserBundle;

use Architect\Contracts\Core\ContainerInterface;
use Architect\Contracts\Core\FrameworkInterface;
use Architect\Support\AbstractBundle;

/**
 * User management bundle for Architect Framework.
 *
 * This bundle provides user management functionality including:
 * - User registration
 * - User authentication
 * - User profile management
 * - User roles and permissions
 */
class UserBundle extends AbstractBundle
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'UserBundle';
    }

    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // Register services
        $container->singleton('user.service', function () {
            return new Service\UserService();
        });

        $container->singleton('user.repository', function () {
            return new Repository\UserRepository();
        });

        $container->singleton('user.auth', function () {
            return new Service\AuthService();
        });

        // Register service provider
        $container->singleton('user.service_provider', function () {
            return new ServiceProvider\UserServiceProvider();
        });

        // Register console commands
        $this->registerCommands($container);
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container, FrameworkInterface $framework): void
    {
        // Initialize services
        $userService = $container->get('user.service');
        $userService->initialize();

        // Register routes
        $this->registerRoutes($container);

        // Register views
        $this->registerViews($container);

        // Publish assets if in development mode
        if ($this->isDevelopment($container)) {
            $this->publishAssets($container);
        }
    }

    /**
     * Register bundle console commands.
     */
    private function registerCommands(ContainerInterface $container): void
    {
        $commands = [
            Command\CreateUserCommand::class,
            Command\ListUsersCommand::class,
            Command\UpdateUserCommand::class,
        ];

        foreach ($commands as $command) {
            if (class_exists($command)) {
                $container->set('command.' . basename(str_replace('\\', '/', $command)), $command);
            }
        }
    }

    /**
     * Register bundle routes.
     */
    private function registerRoutes(ContainerInterface $container): void
    {
        $routes = [
            [
                'path' => '/users',
                'controller' => 'user',
                'action' => 'index',
                'methods' => ['GET'],
                'name' => 'user.index',
            ],
            [
                'path' => '/users/{id}',
                'controller' => 'user',
                'action' => 'show',
                'methods' => ['GET'],
                'name' => 'user.show',
            ],
            [
                'path' => '/users/create',
                'controller' => 'user',
                'action' => 'create',
                'methods' => ['GET', 'POST'],
                'name' => 'user.create',
            ],
            [
                'path' => '/users/{id}/edit',
                'controller' => 'user',
                'action' => 'edit',
                'methods' => ['GET', 'POST'],
                'name' => 'user.edit',
            ],
        ];

        // In a real implementation, you would register these routes with the router
        if ($container->has('router')) {
            // $router = $container->get('router');
            // foreach ($routes as $route) {
            //     $router->addRoute($route);
            // }
        }
    }

    /**
     * Register bundle views.
     */
    private function registerViews(ContainerInterface $container): void
    {
        if ($container->has('template')) {
            $templateService = $container->get('template');

            $viewPath = __DIR__ . '/Resources/views';
            if (is_dir($viewPath) && method_exists($templateService, 'addNamespace')) {
                $templateService->addNamespace('user', $viewPath);
            }
        }
    }

    /**
     * Publish bundle assets.
     */
    private function publishAssets(ContainerInterface $container): void
    {
        $assetsPath = __DIR__ . '/Resources/public';
        if (is_dir($assetsPath)) {
            // In a real implementation, you would use the AssetPublisher
            // $publisher = new \Architect\Core\Bundle\Asset\AssetPublisher();
            // $publisher->publish($this);
        }
    }

    /**
     * Check if we're in development mode.
     */
    private function isDevelopment(ContainerInterface $container): bool
    {
        if ($container->has('environment')) {
            $env = $container->get('environment');
            return $env->getEnvironment() === 'development';
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(ContainerInterface $container, FrameworkInterface $framework): void
    {
        // Cleanup resources
        if ($container->has('user.service')) {
            $service = $container->get('user.service');
            $service->cleanup();
        }
    }
}
