<?php

declare(strict_types=1);

namespace Architect\Support\ServiceProviders;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Services\Mvc\Http\ResponseEmitter;
use Architect\Services\Mvc\Http\ResponseFactory;
use Architect\Services\Mvc\Middleware\MiddlewareDispatcher;
use Architect\Services\Mvc\Middleware\MiddlewareResolver;
use Architect\Support\AbstractServiceProvider;

/**
 * HTTP service provider: PSR-7/15, middleware, response.
 */
class HttpServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // HTTP (PSR-7)
        $this->registerFactory($container, 'http.response_factory', fn() => new ResponseFactory());
        $this->registerFactory($container, 'http.response_emitter', fn() => new ResponseEmitter());

        // Response service (for DI in controllers)
        $this->registerFactory($container, 'response', fn($c) => $c->get('http.response_factory')->createResponse());
        $this->registerAlias($container, 'http.response', 'response');

        // PSR-17 aliases
        $this->registerAlias($container, \Psr\Http\Message\ResponseFactoryInterface::class, 'http.response_factory');
        $this->registerAlias($container, \Psr\Http\Message\StreamFactoryInterface::class, 'http.response_factory');

        // Middleware
        $this->registerFactory($container, 'middleware.resolver', fn($c) => new MiddlewareResolver($c));
        $this->registerFactory($container, 'middleware.dispatcher', fn($c) => new MiddlewareDispatcher($c));

        // Register default middleware aliases
        $this->registerMiddlewareAliases($container);
    }

    /**
     * Register default middleware aliases.
     */
    private function registerMiddlewareAliases(ContainerInterface $container): void
    {
        $resolver = $container->get('middleware.resolver');

        $resolver->aliases([
            // PSR-15 Adapters for existing systems
            'auth' => \Architect\Services\Mvc\Middleware\Adapters\AuthAdapter::class,
            'csrf' => \Architect\Services\Mvc\Middleware\Adapters\CsrfAdapter::class,

            // Built-in middleware
            'rate' => \Architect\Services\Mvc\Middleware\Middlewares\RateLimitMiddleware::class,
        ]);
    }
}
