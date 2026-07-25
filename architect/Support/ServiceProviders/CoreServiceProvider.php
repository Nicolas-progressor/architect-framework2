<?php

declare(strict_types=1);

namespace Architect\Support\ServiceProviders;

use Architect\Contracts\ServiceProviderInterface;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Support\AbstractServiceProvider;
use Architect\Services\Config\ConfigLoader;
use Architect\Services\Config\ConfigPathResolver;
use Architect\Services\Config\Contracts\ConfigLoaderInterface;
use Architect\Services\Routing\HttpRequest;
use Architect\Services\Routing\Loaders\JsonRouteLoader;
use Architect\Services\Routing\Contracts\RouteLoaderInterface;
use Architect\Services\Routing\Contracts\FileSystemInterface;
use Architect\Services\Routing\Filesystem\NativeFileSystem;

/**
 * Core service provider: filesystem, request, configuration.
 */
class CoreServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // Request service
        $this->registerFactory($container, 'request', fn() => new HttpRequest());
        $this->registerAlias($container, 'http.request', 'request');

        // FileSystem service
        $this->registerFactory($container, 'fs', fn() => new NativeFileSystem());
        $this->registerAlias($container, FileSystemInterface::class, 'fs');

        // RouteLoader service
        $this->registerFactory($container, 'route_loader', fn($c) => new JsonRouteLoader($c->get('fs')));
        $this->registerAlias($container, RouteLoaderInterface::class, 'route_loader');

        // Config Path Resolver
        $this->registerFactory($container, 'config.path_resolver', function ($c) {
            $appDir = defined('APP_DIR') ? APP_DIR : dirname(__DIR__, 2) . '/app/';
            $rootDir = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 2) . '/';
            
            return new ConfigPathResolver(
                $c->get('fs'),
                $appDir,
                $rootDir
            );
        });

        // Config Loader
        $this->registerFactory($container, 'config.loader', function ($c) {
            return new ConfigLoader(
                $c->get('fs'),
                $c->get('config.path_resolver')
            );
        });
        $this->registerAlias($container, ConfigLoaderInterface::class, 'config.loader');

        // Config service (default: apps)
        $this->registerFactory($container, 'config', fn($c) => $c->get('config.loader')->load('apps'));

        // Config variants
        $this->registerConfigVariants($container, 'config', [
            'router' => 'router',
            'logger' => 'logger',
            'template' => 'template',
            'debug' => 'debug',
            'lang' => 'lang',
            'helpers' => 'helpers',
            'cache' => 'cache',
        ]);

        // Register container itself as a service for ContainerInterface
        if (!$container->has(ContainerInterface::class)) {
            $container->set(ContainerInterface::class, $container);
        }

        // Override config.lang to use application-specific configuration
        $container->factory('config.lang', function ($c) {
            $loader = $c->get('config.loader');
            $appPath = null;
            if ($c->has('apps')) {
                $apps = $c->get('apps');
                $appPath = $apps->getAppDir();
            }
            return $loader->loadWithAppOverride('lang', $appPath);
        });

        // Override config.router to use application-specific configuration
        $container->factory('config.router', function ($c) {
            $loader = $c->get('config.loader');
            $appPath = null;
            if ($c->has('apps')) {
                $apps = $c->get('apps');
                $appPath = $apps->getAppDir();
            }
            return $loader->loadWithAppOverride('router', $appPath);
        });
    }
}