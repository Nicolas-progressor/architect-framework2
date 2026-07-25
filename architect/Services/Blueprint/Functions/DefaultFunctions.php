<?php

declare(strict_types=1);

namespace Architect\Services\Blueprint\Functions;

use Architect\Core\Container;
use Architect\Services\Blueprint\Contracts\FunctionRegistryInterface;

/**
 * Default Blueprint functions (url, asset, route, config)
 */
final class DefaultFunctions implements FunctionRegistryInterface
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function register(callable $registrar): void
    {
        foreach ($this->getFunctions() as $name => $callback) {
            $registrar($name, $callback);
        }
    }

    public function getFunctions(): array
    {
        return [
            'url' => fn(string $path = ''): string => $this->url($path),
            'asset' => fn(string $path): string => $this->asset($path),
            'route' => fn(string $name, array $params = []): string => $this->route($name, $params),
            'config' => fn(string $key, mixed $default = null): mixed => $this->config($key, $default),
        ];
    }

    private function url(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    private function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }

    private function route(string $name, array $params): string
    {
        if ($this->container->has('router')) {
            $router = $this->container->get('router');
            if (method_exists($router, 'route')) {
                return $router->route($name, $params);
            }
        }

        return '/' . $name;
    }

    private function config(string $key, mixed $default): mixed
    {
        if ($this->container->has('config')) {
            $config = $this->container->get('config');
            if (method_exists($config, 'get')) {
                return $config->get($key, $default);
            }
        }

        return $default;
    }
}
