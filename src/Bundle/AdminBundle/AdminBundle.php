<?php

declare(strict_types=1);

namespace App\Bundle\AdminBundle;

use Architect\Contracts\Core\ContainerInterface;
use Architect\Contracts\Core\FrameworkInterface;
use Architect\Support\AbstractBundle;

class AdminBundle extends AbstractBundle
{
    public function getName(): string
    {
        return 'AdminBundle';
    }

    public function register(ContainerInterface $container): void
    {
        $container->singleton('admin.middleware', function () {
            return new Middleware\AdminMiddleware();
        });
    }

    public function boot(ContainerInterface $container, FrameworkInterface $framework): void
    {
        $this->registerRoutes($container);
        $this->registerViews($container);
    }

    private function registerRoutes(ContainerInterface $container): void
    {
        $routes = [
            ['path' => '/admin', 'controller' => 'dashboard', 'action' => 'index', 'methods' => ['GET'], 'name' => 'admin.dashboard'],
            ['path' => '/admin/users', 'controller' => 'users', 'action' => 'index', 'methods' => ['GET'], 'name' => 'admin.users.index'],
            ['path' => '/admin/users/create', 'controller' => 'users', 'action' => 'create', 'methods' => ['GET', 'POST'], 'name' => 'admin.users.create'],
            ['path' => '/admin/users/{id}/edit', 'controller' => 'users', 'action' => 'edit', 'methods' => ['GET', 'POST'], 'name' => 'admin.users.edit'],
            ['path' => '/admin/users/{id}/delete', 'controller' => 'users', 'action' => 'delete', 'methods' => ['POST'], 'name' => 'admin.users.delete'],
        ];

        if ($container->has('router')) {
            $router = $container->get('router');
            foreach ($routes as $route) {
                $router->addRoute(
                    $route['path'],
                    $route['controller'],
                    $route['action'],
                    $route['methods'],
                );
            }
        }
    }

    private function registerViews(ContainerInterface $container): void
    {
        if ($container->has('template')) {
            $templateService = $container->get('template');
            $viewPath = __DIR__ . '/Resources/views';
            if (is_dir($viewPath) && method_exists($templateService, 'addNamespace')) {
                $templateService->addNamespace('admin', $viewPath);
            }
        }
    }
}
