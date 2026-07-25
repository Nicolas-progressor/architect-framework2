<?php

declare(strict_types=1);

namespace Architect\Services\Mvc;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Services\Mvc\Contracts\ControllerInterface;
use Architect\Services\Mvc\Contracts\ResponseInterface;
use Architect\Services\Mvc\Middleware\Contracts\MiddlewareInterface;

/**
 * Base MVC controller.
 * 
 * Provides common functionality for all controllers including
 * view rendering, model loading, middleware, and response handling.
 */
class Controller implements ControllerInterface
{
    /** @var ContainerInterface Dependency container */
    protected ContainerInterface $container;

    /** @var View View service */
    protected View $view;

    /** @var Model Model service */
    protected Model $model;

    /** @var ResponseInterface HTTP response */
    protected ResponseInterface $response;

    /** @var Resolver\ModulePathResolver Module path resolver */
    protected Resolver\ModulePathResolver $pathResolver;

    /** @var string Current module name */
    protected string $module = '';

    /** @var string Current action name */
    protected string $action = 'index';

    /** @var bool Whether module is global */
    protected bool $isGlobal = false;

    /** @var array Extended data array */
    protected array $ext = [];

    /** @var array<string|class-string<MiddlewareInterface>|array> Middleware configuration */
    protected array $middleware = [];

    /**
     * Create controller instance.
     * 
     * @param ContainerInterface $container Dependency container
     * @param string|null $module Module name
     * @param bool $isGlobal Whether module is global
     */
    public function __construct(
        ContainerInterface $container,
        ?string $module = null,
        bool $isGlobal = false
    ) {
        $this->container = $container;
        $this->isGlobal = $isGlobal;

        // Resolve dependencies from container
        $this->view = $container->get('view');
        $this->model = $container->get('model');
        $this->response = $container->get('response');
        $this->pathResolver = $container->get('module.resolver');

        // Resolve module and action
        $router = $container->get('router');
        $apps = $container->get('apps');

        $this->module = $module ?? $router->getModule() ?: $apps->getDefault();
        $this->action = $router->getAction() ?: 'index';

        // Setup view paths using resolver
        $viewPath = $this->pathResolver->getViewPath($this->module, $isGlobal);
        $modulePath = $this->pathResolver->getModuleBasePath($this->module, $isGlobal);

        $this->view->setTemplateDir($viewPath);
        $this->view->setModulePath($modulePath);

        // Initialize Blueprint if available
        if ($container->has('blueprint')) {
            $this->view->setBlueprint($container->get('blueprint'));
        }

        // Setup model
        $this->model->setModule($this->module);
    }

    /**
     * @inheritdoc
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * @inheritdoc
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @inheritdoc
     */
    public function executeStage(string $action, string $stage): void
    {
        $method = "{$action}_app_{$stage}";

        if (method_exists($this, $method)) {
            $this->{$method}();
        }
    }

    /**
     * Set current module.
     * 
     * @param string $module Module name
     * @param bool $isGlobal Whether module is global
     */
    protected function setModule(string $module, bool $isGlobal = false): void
    {
        $this->module = $module;
        $this->isGlobal = $isGlobal;

        $viewPath = $this->pathResolver->getViewPath($module, $isGlobal);
        $modulePath = $this->pathResolver->getModuleBasePath($module, $isGlobal);

        $this->view->setTemplateDir($viewPath);
        $this->view->setModulePath($modulePath);
        $this->model->setModule($module);
    }

    /**
     * Get view service.
     * 
     * @return View
     */
    public function getView(): View
    {
        return $this->view;
    }

    /**
     * Get view template directory.
     * 
     * @return string
     */
    public function getViewTemplateDir(): string
    {
        return $this->view->getTemplateDir();
    }

    /**
     * Load model by name.
     * 
     * @param string $name Model name
     * @return object|null Model instance or null
     */
    protected function getModel(string $name): ?object
    {
        return $this->model->load($name);
    }

    /**
     * Render view template.
     * 
     * @param string $template Template name
     * @param array $data Template data
     * @return string Rendered content
     */
    protected function render(string $template, array $data = []): string
    {
        return $this->view->render($template, array_merge($this->ext, $data));
    }

    /**
     * Display view template.
     * 
     * @param string $template Template name
     * @param array $data Template data
     */
    protected function display(string $template, array $data = []): void
    {
        $this->view->display($template, array_merge($this->ext, $data));
    }

    /**
     * Set JSON response.
     * 
     * @param mixed $data Data to encode
     * @param int $statusCode HTTP status code
     * @param int $options JSON encode options
     */
    protected function json(mixed $data, int $statusCode = 200, int $options = 0): void
    {
        $this->response = $this->response
            ->withStatus($statusCode)
            ->withJson($data, $options);
    }

    /**
     * Set HTML content response.
     * 
     * @param string $content HTML content
     */
    protected function html(string $content): void
    {
        $this->response = $this->response->withContent($content);
    }

    /**
     * Set text response.
     * 
     * @param string $text Text content
     */
    protected function text(string $text): void
    {
        $this->response = $this->response
            ->withContent($text)
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withType('text');
    }

    /**
     * Prepare redirect (without immediate exit).
     * 
     * @param string $url Redirect URL
     * @param int $status HTTP status code
     */
    protected function redirectTo(string $url, int $status = 302): void
    {
        $this->response = $this->response->withRedirect($url, $status);
    }

    /**
     * Get response object.
     * 
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get URL parameter.
     * 
     * @param string $name Parameter name
     * @param string $default Default value
     * @return string
     */
    protected function param(string $name, string $default = ''): string
    {
        return $this->container->get('router')->getParam($name, $default);
    }

    /**
     * Get URL segment by index.
     * 
     * @param int $index Segment index (1-based)
     * @param string $default Default value
     * @return string
     */
    protected function segment(int $index, string $default = ''): string
    {
        return $this->container->get('router')->segment($index, $default);
    }

    /**
     * Get service from container.
     * 
     * @param string $id Service identifier
     * @return mixed
     */
    protected function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    // === Template Management ===

    /**
     * Set template by name.
     * 
     * @param string $name Template name
     */
    protected function setTemplate(string $name): void
    {
        $this->container->get('template')->setTemplate($name);
    }

    /**
     * Disable template (output content only).
     */
    protected function noTemplate(): void
    {
        $this->container->get('template')->disable();
    }

    /**
     * Enable template.
     */
    protected function useTemplate(): void
    {
        $this->container->get('template')->enable();
    }

    /**
     * Check if template is enabled.
     * 
     * @return bool
     */
    protected function hasTemplate(): bool
    {
        return $this->container->get('template')->isEnabled();
    }

    // === Middleware Management ===

    /**
     * Get middleware configuration.
     * 
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Register middleware.
     * 
     * @param string|class-string<MiddlewareInterface> $middleware Middleware class or alias
     * @param array $options Middleware options (only, except)
     * @return static
     */
    protected function addMiddleware(string $middleware, array $options = []): static
    {
        if (!empty($options)) {
            $this->middleware[] = array_merge([$middleware], $options);
        } else {
            $this->middleware[] = $middleware;
        }
        return $this;
    }

    /**
     * Register middleware for specific actions only.
     * 
     * @param string|class-string<MiddlewareInterface> $middleware Middleware class or alias
     * @param array<string> $actions Action names
     * @return static
     */
    protected function middlewareOnly(string $middleware, array $actions): static
    {
        return $this->addMiddleware($middleware, ['only' => $actions]);
    }

    /**
     * Register middleware for all actions except specified.
     * 
     * @param string|class-string<MiddlewareInterface> $middleware Middleware class or alias
     * @param array<string> $actions Action names to exclude
     * @return static
     */
    protected function middlewareExcept(string $middleware, array $actions): static
    {
        return $this->addMiddleware($middleware, ['except' => $actions]);
    }

    /**
     * Clear all middleware.
     * 
     * @return static
     */
    protected function clearMiddleware(): static
    {
        $this->middleware = [];
        return $this;
    }
}
