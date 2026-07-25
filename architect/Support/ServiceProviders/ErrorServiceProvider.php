<?php

declare(strict_types=1);

namespace Architect\Support\ServiceProviders;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Support\AbstractServiceProvider;

/**
 * Error service provider: errors, form, error handlers.
 */
class ErrorServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // Errors service - with proper DI
        $this->registerFactory($container, 'errors', function ($c) {
            return new \Architect\Services\Errors\Errors(
                $c->get('logger'),
                $c->has('template') ? $c->get('template') : null,
                $c->has('config') ? $c->get('config') : null,
                $c->get('environment')
            );
        });
        $this->registerAlias($container, 'error.handler', 'errors');

        // Form service — внедрение зависимостей через интерфейсы
        $this->registerFactory($container, 'form', function ($c) {
            return new \Architect\Services\Form\Form(
                new \Architect\Services\Form\CSRFTokenManager(),
                new \Architect\Services\Form\FormBuilder(),
                new \Architect\Services\Form\FormValidator()
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container): void
    {
        // Initialize error handlers
        $errors = $container->get('errors');

        // Pass container for accessing services (router, debug, etc.)
        $errors->setContainer($container);

        $errors->init();
    }
}
