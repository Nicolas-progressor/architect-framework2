<?php

declare(strict_types=1);

namespace Architect\Services\Blueprint;

use Architect\Contracts\ServiceProviderInterface;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Services\Blueprint\Contracts\BlueprintConfigInterface;
use Architect\Services\Blueprint\Contracts\ContextManagerInterface;
use Architect\Services\Blueprint\Contracts\ElementRendererInterface;
use Architect\Services\Blueprint\Contracts\FunctionRegistryInterface;
use Architect\Services\Blueprint\Contracts\FilterRegistryInterface;
use Architect\Services\Blueprint\Config\ConfigLoader;
use Architect\Services\Blueprint\Config\BlueprintConfig;
use Architect\Services\Blueprint\Context\ContextManager;
use Architect\Services\Blueprint\Elements\ElementConfigLoader;
use Architect\Services\Blueprint\Elements\MvcElementRenderer;
use Architect\Services\Blueprint\Elements\RoutedElementResolver;
use Architect\Services\Blueprint\Elements\ElementRenderer;
use Architect\Services\Blueprint\Functions\DefaultFunctions;
use Architect\Services\Blueprint\Filters\DefaultFilters;

/**
 * Blueprint Service Provider for Architect Framework
 * 
 * Registers Blueprint services with DI container following SOLID principles
 */
final class BlueprintServiceProvider implements ServiceProviderInterface
{
    private ?ContainerInterface $container = null;
    
    /** @var FunctionRegistryInterface[] */
    private array $functionRegistries = [];
    
    /** @var FilterRegistryInterface[] */
    private array $filterRegistries = [];

    /**
     * Optionally accept container in constructor for backward compatibility.
     */
    public function __construct(?ContainerInterface $container = null)
    {
        if ($container !== null) {
            $this->container = $container;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // Use the container passed to register if not already set
        if ($this->container === null) {
            $this->container = $container;
        }

        // Register context manager with container
        $this->container->factory('blueprint.context', fn($c) => new ContextManager(null, $c));
        
        // Register config loader
        $this->container->factory('blueprint.config', fn($c) => $this->createConfig($c));
        
        // Register element renderer components
        $this->container->factory('blueprint.element_config_loader', fn($c) => new ElementConfigLoader());
        $this->container->factory('blueprint.mvc_renderer', fn($c) => new MvcElementRenderer($c, $c->get('blueprint.config')));
        $this->container->factory('blueprint.routed_resolver', fn($c) => new RoutedElementResolver($c));
        $this->container->factory('blueprint.elements', fn($c) => $this->createElementRenderer($c));
        
        // Register main service - initialize immediately
        $this->container->factory('blueprint', fn($c) => $this->createBlueprintService($c));
        
        // Register default function/filter registries
        $this->addFunctionRegistry(new DefaultFunctions($this->container));
        $this->addFilterRegistry(new DefaultFilters());
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container): void
    {
        // Ensure container is set
        if ($this->container === null) {
            $this->container = $container;
        }

        if (!$this->container->has('blueprint')) {
            return;
        }
        
        $blueprint = $this->container->get('blueprint');
        
        // Register functions and filters
        $this->registerFunctions($blueprint);
        $this->registerFilters($blueprint);
        
        // Setup element extension
        $this->setupElementExtension();
        
        // Setup debug integration
        $this->setupDebugIntegration();
        
        // Note: context is set via statement in ServiceProvider::configureStatements
    }

    /**
     * Add function registry
     */
    public function addFunctionRegistry(FunctionRegistryInterface $registry): void
    {
        $this->functionRegistries[] = $registry;
    }

    /**
     * Add filter registry
     */
    public function addFilterRegistry(FilterRegistryInterface $registry): void
    {
        $this->filterRegistries[] = $registry;
    }

    /**
     * Create configuration
     */
    private function createConfig(ContainerInterface $container): BlueprintConfigInterface
    {
        $loader = new ConfigLoader($container);
        return $loader->load();
    }

    /**
     * Create element renderer
     */
    private function createElementRenderer(ContainerInterface $container): ElementRendererInterface
    {
        return new ElementRenderer(
            $container->get('blueprint.element_config_loader'),
            $container->get('blueprint.mvc_renderer'),
            $container->get('blueprint.routed_resolver'),
            $container->get('blueprint.context'),
            $container->get('blueprint.config')
        );
    }
        
    /**
     * Create Blueprint service
     */
    private function createBlueprintService(ContainerInterface $container): BlueprintService
    {
        $service = new BlueprintService($container);
        
        $service->initialize(
            $container->get('blueprint.config'),
            $container->get('blueprint.context')
        );
        
        $service->setElementRenderer($container->get('blueprint.elements'));
        
        return $service;
    }

    /**
     * Register all functions
     */
    private function registerFunctions(BlueprintService $blueprint): void
    {
        foreach ($this->functionRegistries as $registry) {
            $registry->register(fn(string $name, callable $fn) => $blueprint->registerFunction($name, $fn));
        }
    }
        
    /**
     * Register all filters
     */
    private function registerFilters(BlueprintService $blueprint): void
    {
        foreach ($this->filterRegistries as $registry) {
            $registry->register(fn(string $name, callable $filter) => $blueprint->registerFilter($name, $filter));
        }
    }
        
    /**
     * Setup element extension
     */
    private function setupElementExtension(): void
    {
        $elementRenderer = $this->container->get('blueprint.elements');
        $extension = new BlueprintExtension($elementRenderer);
        
        $blueprint = $this->container->get('blueprint');
        $extension->register($blueprint->getBlueprint());
    }

    /**
     * Setup debug integration
     */
    private function setupDebugIntegration(): void
    {
        if (!$this->container->has('debug')) {
            return;
        }
        
        $debug = $this->container->get('debug');
        
        if (method_exists($debug, 'isEnabled') && !$debug->isEnabled()) {
            return;
        }
        
        $blueprintService = $this->container->get('blueprint');
        
        // Get underlying Blueprint instance and init debug data
        try {
            $blueprint = $blueprintService->getBlueprint();
            $blueprint->initDebugData();
        } catch (\RuntimeException $e) {
            // Blueprint not initialized yet
        }
    }
}
