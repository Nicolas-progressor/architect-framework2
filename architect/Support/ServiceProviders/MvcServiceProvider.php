<?php

declare(strict_types=1);

namespace Architect\Support\ServiceProviders;

use Architect\Contracts\ServiceProviderInterface;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Support\AbstractServiceProvider;
use Architect\Services\Mvc\View;
use Architect\Services\Mvc\Model;
use Architect\Services\Mvc\Pattern;
use Architect\Services\Mvc\Context\MvcContext;
use Architect\Services\Mvc\Resolver\ModulePathResolver;
use Architect\Services\Mvc\Loader\ControllerLoader;
use Architect\Services\Mvc\Loader\ModuleBootstrapLoader;
use Architect\Services\Mvc\Handler\ErrorHandler404;
use Architect\Services\Mvc\Renderer;
use Architect\Services\Template\TemplateServiceProvider;
use Architect\Services\I18n\LanguageServiceProvider;

/**
 * MVC service provider: view, model, pattern, MVC components.
 */
class MvcServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // View service
        $this->registerFactory($container, 'view', fn($c) => new View($c));

        // Model service
        $this->registerFactory($container, 'model', fn($c) => new Model($c, ''));

        // Module Path Resolver (for MVC)
        $this->registerFactory($container, 'module.resolver', fn($c) => new ModulePathResolver($c));

        // MVC Context
        $this->registerFactory($container, 'mvc.context', fn() => new MvcContext());

        // MVC Loaders
        $this->registerFactory($container, 'mvc.controller_loader', fn($c) => new ControllerLoader(
            $c,
            $c->get('module.resolver')
        ));

        $this->registerFactory($container, 'mvc.bootstrap_loader', fn($c) => new ModuleBootstrapLoader(
            $c,
            $c->get('module.resolver')
        ));

        // MVC Handlers
        $this->registerFactory($container, 'mvc.error_handler_404', fn($c) => new ErrorHandler404(
            $c,
            $c->get('module.resolver')
        ));

        // MVC Renderer
        $this->registerFactory($container, 'mvc.renderer', fn($c) => new Renderer(
            $c,
            $c->get('mvc.context')
        ));

        // Pattern service
        $this->registerFactory($container, 'pattern', fn($c) => new Pattern(
            $c,
            $c->get('mvc.context'),
            $c->get('module.resolver'),
            $c->get('mvc.controller_loader'),
            $c->get('mvc.bootstrap_loader'),
            $c->get('mvc.error_handler_404')
        ));

        // Register TemplateServiceProvider (if not already registered)
        $templateProvider = new TemplateServiceProvider();
        $templateProvider->register($container);

        // Register LanguageServiceProvider (if not already registered)
        $languageProvider = new LanguageServiceProvider();
        $languageProvider->register($container);
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container): void
    {
        // Boot Pattern service to register its statement hooks
        $pattern = $container->get('pattern');
        if (method_exists($pattern, 'boot')) {
            $pattern->boot();
        }
    }
}