<?php

declare(strict_types=1);

namespace Architect\Support\ServiceProviders;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Services\App\Apps;
use Architect\Services\Routing\ModuleResolver;
use Architect\Services\Routing\Router;
use Architect\Support\AbstractServiceProvider;

/**
 * Routing service provider: apps, module resolver, router.
 */
class RoutingServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // Apps service (needed for ModuleResolver)
        $this->registerFactory($container, 'apps', function ($c) {
            $apps = new Apps(
                $c,
                $c->get('statement'),
                $c->get('config.loader'),
                $c->get('logger')
            );

            // Set lazy router resolver to avoid circular dependency
            $apps->setRouterResolver(fn() => $c->get('router'));

            return $apps;
        });

        // ModuleResolver service
        $this->registerFactory($container, 'module_resolver', fn($c) => new ModuleResolver(
            $c->get('apps'),
            $c->get('fs')
        ));

        // Router service
        $this->registerFactory($container, 'router', fn($c) => new Router(
            $c,
            $c->get('request'),
            $c->get('route_loader'),
            $c->get('module_resolver'),
            $c->get('config.router'),
            $c->get('apps'),
            $c->get('fs')
        ));
    }
}
