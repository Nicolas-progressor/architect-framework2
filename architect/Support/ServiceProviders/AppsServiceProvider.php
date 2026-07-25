<?php

declare(strict_types=1);

namespace Architect\Support\ServiceProviders;

use Architect\Contracts\ServiceProviderInterface;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Support\AbstractServiceProvider;
use Architect\Services\App\Apps;
use Architect\Services\App\Contracts\AppsServiceInterface;
use Architect\Services\App\AppConfigLoader;
use Architect\Services\App\AppBootstrapLoader;
use Architect\Services\Config\Contracts\ConfigLoaderInterface;
use Architect\Core\Contracts\StatementInterface;
use Psr\Log\LoggerInterface;

/**
 * Apps service provider: registers the Apps service and its dependencies.
 */
class AppsServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // Register Apps as a factory with lazy router resolver
        $this->registerFactory($container, 'apps', function ($c) {
            $apps = new Apps(
                $c,
                $c->get(StatementInterface::class),
                $c->get(ConfigLoaderInterface::class),
                $c->has('logger') ? $c->get(LoggerInterface::class) : null
            );

            // Set router resolver (lazy) to break circular dependency
            $apps->setRouterResolver(function () use ($c) {
                return $c->get('router');
            });

            return $apps;
        });

        $this->registerAlias($container, AppsServiceInterface::class, 'apps');

        // Register internal loaders (they are used internally by Apps)
        $this->registerFactory($container, 'app.config_loader', function ($c) {
            return new AppConfigLoader(
                $c->has('logger') ? $c->get(LoggerInterface::class) : null
            );
        });

        $this->registerFactory($container, 'app.bootstrap_loader', function ($c) {
            return new AppBootstrapLoader(
                $c->get(StatementInterface::class),
                $c->has('logger') ? $c->get(LoggerInterface::class) : null
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container): void
    {
        $apps = $container->get('apps');
        if (method_exists($apps, 'boot')) {
            $apps->boot();
        }
    }
}