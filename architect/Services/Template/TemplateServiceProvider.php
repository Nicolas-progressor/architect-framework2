<?php

declare(strict_types=1);

namespace Architect\Services\Template;

use Architect\Contracts\ServiceProviderInterface;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Services\Template\Blueprint\BlueprintAdapter;
use Architect\Services\Template\Config\TemplateConfigLoader;
use Architect\Services\Template\Contracts\BlueprintAdapterInterface;
use Architect\Services\Template\Contracts\ElementLoaderInterface;
use Architect\Services\Template\Contracts\PathResolverInterface;
use Architect\Services\Template\Contracts\TemplateConfigLoaderInterface;
use Architect\Services\Template\Contracts\WidgetRendererInterface;
use Architect\Services\Template\ElementLoader\ElementLoader;
use Architect\Services\Template\PathResolver\TemplatePathResolver;
use Architect\Services\Template\Renderer\BlueprintRenderer;
use Architect\Services\Template\Renderer\PhpRenderer;
use Architect\Services\Template\Renderer\RendererChain;
use Architect\Services\Template\WidgetRenderer\WidgetRenderer;

/**
 * Service provider for Template services.
 */
final class TemplateServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // PathResolver
        $container->factory(PathResolverInterface::class, function () use ($container) {
            return new TemplatePathResolver(
                $container->get('apps')
            );
        });

        // ElementLoader
        $container->factory(ElementLoaderInterface::class, function () {
            return new ElementLoader();
        });

        // WidgetRenderer
        $container->factory(WidgetRendererInterface::class, function () use ($container) {
            return new WidgetRenderer(
                $container,
                $container->get('apps')
            );
        });

        // TemplateConfigLoader
        $container->factory(TemplateConfigLoaderInterface::class, function () {
            return new TemplateConfigLoader();
        });

        // BlueprintAdapter - inject existing Blueprint if available
        $container->factory(BlueprintAdapterInterface::class, function () use ($container) {
            $blueprintInstance = null;

            // Try to get existing Blueprint from container
            if ($container->has('blueprint')) {
                try {
                    $blueprintService = $container->get('blueprint');
                    // BlueprintService has getBlueprint() method
                    if (method_exists($blueprintService, 'getBlueprint')) {
                        $blueprintInstance = $blueprintService->getBlueprint();
                    }
                } catch (\Throwable) {
                    // Ignore - will create new instance
                }
            }

            return new BlueprintAdapter($blueprintInstance);
        });

        // RendererChain
        $container->factory(RendererChain::class, function () use ($container) {
            $phpRenderer = new PhpRenderer();
            $phpRenderer->setContainer($container);

            $chain = new RendererChain();
            $chain->add(new BlueprintRenderer(
                $container->get(BlueprintAdapterInterface::class)
            ));
            $chain->add($phpRenderer);
            return $chain;
        });

        // Template
        $container->factory(Template::class, function () use ($container) {
            return new Template(
                $container->get('router'),
                $container->get(PathResolverInterface::class),
                $container->get(ElementLoaderInterface::class),
                $container->get(WidgetRendererInterface::class),
                $container->get(TemplateConfigLoaderInterface::class),
                $container->get(RendererChain::class)
            );
        });

        // Alias for backward compatibility
        $container->factory('template', function () use ($container) {
            return $container->get(Template::class);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container): void
    {
        // No boot actions required for Template services
    }
}
