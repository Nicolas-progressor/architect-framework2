<?php

declare(strict_types=1);

namespace Architect\Services\Mvc;

use Architect\Support\AbstractService;
use Architect\Services\Mvc\Contracts\PatternInterface;
use Architect\Services\Mvc\Context\MvcContext;
use Architect\Services\Mvc\Contracts\ControllerInterface;
use Architect\Services\Mvc\Loader\ControllerLoader;
use Architect\Services\Mvc\Loader\ModuleBootstrapLoader;
use Architect\Services\Mvc\Handler\ErrorHandler404;
use Architect\Services\Mvc\Resolver\ModulePathResolver;

/**
 * Pattern service for MVC request handling.
 * 
 * Coordinates the MVC lifecycle including controller loading,
 * module bootstrap initialization, and stage execution.
 * Acts as a facade for the MVC components.
 * 
 * @package Architect\Services\Mvc
 */
class Pattern extends AbstractService implements PatternInterface
{
    /** @var MvcContext MVC request context */
    private MvcContext $context;

    /** @var ModulePathResolver Module path resolver */
    private ModulePathResolver $pathResolver;

    /** @var ControllerLoader Controller loader */
    private ControllerLoader $controllerLoader;

    /** @var ModuleBootstrapLoader Module bootstrap loader */
    private ModuleBootstrapLoader $bootstrapLoader;

    /** @var ErrorHandler404 Error handler */
    private ErrorHandler404 $errorHandler;

    /** @var ControllerInterface|null Current controller instance */
    private ?ControllerInterface $controllerInstance = null;

    /**
     * Create Pattern service.
     * 
     * @param \Architect\Core\Contracts\ContainerInterface $container Dependency container
     * @param MvcContext|null $context MVC context
     * @param ModulePathResolver|null $pathResolver Module path resolver
     * @param ControllerLoader|null $controllerLoader Controller loader
     * @param ModuleBootstrapLoader|null $bootstrapLoader Bootstrap loader
     * @param ErrorHandler404|null $errorHandler Error handler
     */
    public function __construct(
        \Architect\Core\Contracts\ContainerInterface $container,
        ?MvcContext $context = null,
        ?ModulePathResolver $pathResolver = null,
        ?ControllerLoader $controllerLoader = null,
        ?ModuleBootstrapLoader $bootstrapLoader = null,
        ?ErrorHandler404 $errorHandler = null
    ) {
        parent::__construct($container);

        $this->context = $context ?? new MvcContext();
        $this->pathResolver = $pathResolver ?? $container->get('module.resolver');

        // Initialize loaders and handlers
        $this->controllerLoader = $controllerLoader ?? new ControllerLoader($container, $this->pathResolver);
        $this->bootstrapLoader = $bootstrapLoader ?? new ModuleBootstrapLoader($container, $this->pathResolver);
        $this->errorHandler = $errorHandler ?? new ErrorHandler404($container, $this->pathResolver);
    }

    /**
     * Get MVC context.
     * 
     * @return MvcContext
     */
    public function getContext(): MvcContext
    {
        return $this->context;
    }

    /**
     * Get controller loader.
     * 
     * @return ControllerLoader
     */
    public function getControllerLoader(): ControllerLoader
    {
        return $this->controllerLoader;
    }

    /**
     * Get bootstrap loader.
     * 
     * @return ModuleBootstrapLoader
     */
    public function getBootstrapLoader(): ModuleBootstrapLoader
    {
        return $this->bootstrapLoader;
    }
    
    /**
     * Get error handler.
     * 
     * @return ErrorHandler404
     */
    public function getErrorHandler(): ErrorHandler404
    {
        return $this->errorHandler;
    }

    /**
     * Boot the service.
     * 
     * Registers statement hooks for MVC lifecycle.
     */
    public function boot(): void
    {
        $statement = $this->get('statement');

        $statement->on('core_load', fn($c) => $this->handleRequest(), 5);

        $statement->on('app_load', fn($c) => $this->executeStage('load'), 10);
        $statement->on('app_data', fn($c) => $this->executeStage('data'), 10);
        $statement->on('app_output', fn($c) => $this->executeStage('output'), 10);
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        // Pattern runs through statement hooks
    }

    /**
     * Handle incoming request.
     * 
     * Initializes context, determines module type, loads controller
     * and module bootstrap.
     */
    private function handleRequest(): void
    {
        $this->context->reset();

        $router = $this->get('router');
        $apps = $this->get('apps');

        $this->context
            ->setModule($router->getModule())
            ->setController($router->getController())
            ->setAction($router->getAction() ?: 'index');

        if (!$router->hasRoute()) {
            $this->handle404Route();
        } else {
            $this->determineModuleType();
        }

        $this->loadController();
        $this->loadModuleBootstrap();
    }

    /**
     * Handle 404 route.
     * 
     * Attempts to use app-level 404 controller first, then global.
     */
    private function handle404Route(): void
    {
        if ($this->errorHandler->hasApp404()) {
            $this->context
                ->setModule('_404')
                ->setController('_404')
                ->setAction('index');
        } elseif ($this->errorHandler->hasGlobal404()) {
            $this->context
                ->setModule('_404')
                ->setController('_404')
                ->setAction('index')
                ->setGlobal404(true);
        } else {
            $this->errorHandler->handleFatal('Page not found');
        }
    }

    /**
     * Determine module type (app or global).
     * 
     * Checks if module exists in app modules, then in global modules.
     */
    private function determineModuleType(): void
    {
        $module = $this->context->getModule();

        if ($this->pathResolver->isGlobalModule($module)) {
            $this->context->setGlobalModule(true);
        } elseif (!$this->pathResolver->moduleExists($module, false)) {
            $apps = $this->get('apps');
            $this->context->setModule($apps->getDefaultApp());
        }
    }

    /**
     * Load controller.
     * 
     * Uses ControllerLoader to load and instantiate the controller.
     */
    private function loadController(): void
    {
        $module = $this->context->getModule();
        $controller = $this->context->getController();
        $isGlobal = $this->context->isGlobalModule();

        $this->controllerInstance = $this->controllerLoader->load($module, $controller, $isGlobal);

        if ($this->controllerInstance === null) {
            $this->errorHandler->handle("Controller not found: {$controller}");
        }
    }

    /**
     * Load module bootstrap.
     * 
     * Uses ModuleBootstrapLoader to load and register bootstrap.
     */
    private function loadModuleBootstrap(): void
    {
        $module = $this->context->getModule();
        $isGlobal = $this->context->isGlobalModule();

        $bootstrap = $this->bootstrapLoader->load($module, $isGlobal);

        if ($bootstrap !== null) {
            $this->context->setModuleBootstrap($bootstrap);
            $this->bootstrapLoader->registerStatementHandlers($bootstrap);
        }
    }

    /**
     * Execute stage method on controller.
     * 
     * @param string $stage Stage name (load, data, output)
     */
    private function executeStage(string $stage): void
    {
        if (!$this->controllerInstance) {
            return;
        }

        $action = $this->context->getAction();
        $this->controllerInstance->executeStage($action, $stage);
    }

    /**
     * Get current module name.
     * 
     * @return string|null
     */
    public function getModule(): ?string
    {
        return $this->context->getModule();
    }

    /**
     * Get current controller name.
     * 
     * @return string|null
     */
    public function getController(): ?string
    {
        return $this->context->getController();
    }

    /**
     * Get current action name.
     * 
     * @return string
     */
    public function getAction(): string
    {
        return $this->context->getAction();
    }

    /**
     * Get current controller instance.
     * 
     * @return ControllerInterface|null
     */
    public function getControllerInstance(): ?ControllerInterface
    {
        return $this->controllerInstance;
    }

    /**
     * Render output.
     * 
     * Renders controller view if extArray method exists.
     */
    public function renderOutput(): void
    {
        if ($this->controllerInstance && method_exists($this->controllerInstance, 'extArray')) {
            $ext = $this->controllerInstance->ext ?? [];
            echo $this->get('view')->render($this->context->getController(), $ext, false) ?? '';
        }
    }
}

// PS> docker-compose exec -T php_arc2 php arc cache:clear 2>&1