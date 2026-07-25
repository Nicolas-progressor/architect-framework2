<?php

declare(strict_types=1);

namespace Architect\Support;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Core\Contracts\StatementInterface;
use Architect\Services\Template\Contracts\TemplateConfigLoaderInterface;

/**
 * Configures statement lifecycle hooks for the application.
 */
class StatementConfigurator
{
    /**
     * Configure all statement hooks.
     */
    public function configure(StatementInterface $statement, ContainerInterface $container): void
    {
        // core_preinit: Clear breadcrumbs
        $statement->on('core_preinit', function ($container) {
            if (class_exists('Unit', false)) {
                \Statics::Breadcrumbs()->clear();
            }
        }, 5);

        // core_init: Initialize app and routing
        $statement->on('core_init', function ($container) {
            $logger = $container->get('logger');
            $logger->logWithChannel('debug', 'Statement: core_init', [], 'system');

            $apps = $container->get('apps');
            $router = $container->get('router');
            $router->loadRoutes($apps->getAppDir());

            // Template initialization
            $template = $container->get('template');
            $configLoader = $container->get(TemplateConfigLoaderInterface::class);

            $appDir = $apps->getAppDir();
            $logger->logWithChannel('debug', 'Template init', [
                'appDir' => $appDir,
                'currentApp' => $apps->getCurrentApp(),
            ], 'template');

            $configLoader->setAppDir($appDir);

            // Boot template with config
            if (method_exists($template, 'boot')) {
                $template->boot();
            }

            $logger->logWithChannel('debug', 'Template after boot', [
                'templatePath' => $template->getTemplatePath(),
                'templateName' => $template->getTemplateName(),
                'isEnabled' => $template->isEnabled(),
                'configTemplate' => $configLoader->getDefaultTemplate(),
            ], 'template');

            if ($configLoader->isNotemplateGet()) {
                $template->disable();
            }

            // Initialize Blueprint context
            $this->initBlueprintContext($container, $apps->getAppDir(), $configLoader->getDefaultTemplate());
        }, 5);

        // core_post_load: Handle route-specific template settings
        $statement->on('core_post_load', function ($container) {
            $apps = $container->get('apps');
            $template = $container->get('template');

            // Use ConfigLoader instead of direct Config instantiation
            $configLoader = $container->get('config.loader');
            $configTemplate = $configLoader->load('template', $apps->getAppDir());

            // Get MVC context
            $mvcContext = $container->get('mvc.context');
            $isGlobalModule = $mvcContext->isGlobalModule();

            if ($isGlobalModule) {
                $template->disable();
                return;
            }

            $router = $container->get('router');
            $routeTemplate = $router->getParam('__template');
            if ($routeTemplate) {
                $template->setTemplate($routeTemplate);
            }

            $routeNoTemplate = $router->getParam('__notemplate');
            if ($routeNoTemplate) {
                $template->disable();
            }
        }, 20);

        // render: Render response via Renderer
        $statement->on('render', function ($container) {
            $logger = $container->get('logger');
            $logger->logWithChannel('debug', 'Statement: render', [], 'system');

            // Use Renderer to output response
            $renderer = $container->get('mvc.renderer');
            $renderer->render();
        }, 100);
    }

    /**
     * Initialize Blueprint context.
     */
    private function initBlueprintContext(ContainerInterface $container, string $appDir, ?string $templateName): void
    {
        if (!$container->has('blueprint')) {
            return;
        }

        $blueprint = $container->get('blueprint');

        if (method_exists($blueprint, 'setContext')) {
            $blueprint->setContext($appDir, $templateName);
        }
    }
}
