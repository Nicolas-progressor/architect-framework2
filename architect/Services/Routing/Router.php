<?php

declare(strict_types=1);

namespace Architect\Services\Routing;

use Architect\Support\AbstractService;
use Architect\Services\Routing\Contracts\RouterInterface;
use Architect\Services\Routing\Contracts\RequestInterface;
use Architect\Services\Routing\Contracts\RouteLoaderInterface;
use Architect\Services\Routing\Contracts\FileSystemInterface;
use Architect\Services\App\Contracts\AppsServiceInterface;
use Architect\Services\Config\Contracts\ConfigInterface;
use Architect\Services\Routing\Filesystem\NativeFileSystem;

/**
 * Router service for URL routing and resolution.
 */
class Router extends AbstractService implements RouterInterface
{
    private RequestInterface $request;
    private RouteLoaderInterface $loader;
    private ModuleResolver $moduleResolver;
    private FileSystemInterface $fs;
    private AppsServiceInterface $apps;
    private ConfigInterface $config;

    /** @var string Current path */
    public string $path = '/';

    /** @var array URL segments */
    public array $segments = [];

    /** @var array Route parameters */
    public array $params = [];

    /** @var string Current module */
    private string $module = '';

    /** @var string Current controller */
    private string $controller = '';

    /** @var string Current action */
    private string $action = '';

    /** @var array Loaded routes */
    public array $routes = [];

    /** @var RouteCacheInterface|null Route cache */
    private ?RouteCacheInterface $cache = null;

    /** @var array Router configuration */
    private array $defaults = [
        'module' => 'home',
        'controller' => 'home',
        'action' => 'index',
        '404_module' => '_404',
        '404_controller' => '_404',
        '404_action' => 'index',
    ];

    /**
     * Create router instance.
     */
    public function __construct(
        \Architect\Core\Contracts\ContainerInterface $container,
        RequestInterface $request,
        RouteLoaderInterface $loader,
        ModuleResolver $moduleResolver,
        ConfigInterface $config,
        AppsServiceInterface $apps,
        ?FileSystemInterface $fs = null,
        ?RouteCacheInterface $cache = null
    ) {
        parent::__construct($container);

        $this->request = $request;
        $this->loader = $loader;
        $this->moduleResolver = $moduleResolver;
        $this->config = $config;
        $this->apps = $apps;
        $this->fs = $fs ?? new NativeFileSystem();
        $this->cache = $cache;

        $this->path = $request->getPath();
        $this->segments = $request->getSegments();
        $this->loadDefaults();
    }

    /**
     * Boot the service.
     */
    public function boot(): void
    {
        // Dependencies already injected
    }

    /**
     * Load router configuration.
     */
    private function loadDefaults(): void
    {
        $routerConfig = $this->config->get('router', []);

        if (isset($routerConfig['default_module'])) {
            $this->defaults['module'] = $routerConfig['default_module'];
        }
        if (isset($routerConfig['default_controller'])) {
            $this->defaults['controller'] = $routerConfig['default_controller'];
        }
        if (isset($routerConfig['default_action'])) {
            $this->defaults['action'] = $routerConfig['default_action'];
        }
        if (isset($routerConfig['404_module'])) {
            $this->defaults['404_module'] = $routerConfig['404_module'];
        }
        if (isset($routerConfig['404_controller'])) {
            $this->defaults['404_controller'] = $routerConfig['404_controller'];
        }
        if (isset($routerConfig['404_action'])) {
            $this->defaults['404_action'] = $routerConfig['404_action'];
        }
    }

    /**
     * Load routes from application directory.
     */
    public function loadRoutes(string $appDir): void
    {
        // Try to get from cache first
        if ($this->cache !== null && $this->cache->has($appDir)) {
            $cachedRoutes = $this->cache->get($appDir);
            if ($cachedRoutes !== null) {
                $this->routes = $cachedRoutes;
                $this->resolve();
                return;
            }
        }

        // Global routes
        $this->routes = $this->loader->loadDirectory(APP_DIR . 'routes/');

        // App config routes (config/routes.json) - higher priority than routes/
        $configRoutes = [];
        if (method_exists($this->loader, 'loadConfig')) {
            $configRoutes = $this->loader->loadConfig($appDir . 'config/routes.json');
        } else {
            // Fallback: try to load via load() and merge manually
            $configFile = $appDir . 'config/routes.json';
            if ($this->fs->exists($configFile)) {
                $data = $this->loader->load($configFile);
                if (isset($data['routes'])) {
                    $configRoutes = $data['routes'];
                    // Add aliases
                    if (isset($data['routes']['/'])) {
                        $configRoutes['index'] ??= $data['routes']['/'];
                        $configRoutes['default'] ??= $data['routes']['/'];
                    }
                }
            }
        }

        // App routes (merge) - routes/ directory
        $appRoutes = $this->loader->loadDirectory($appDir . 'routes/');

        // Merge: config routes first, then app routes (app routes can override)
        $this->routes = array_merge($this->routes, $configRoutes, $appRoutes);

        // Module routes (merge)
        $currentModule = $this->segments[0] ?? $this->defaults['module'];
        $moduleRoutes = $this->loader->loadDirectory($appDir . "modules/{$currentModule}/routes/");
        $this->routes = array_merge($this->routes, $moduleRoutes);

        // Store in cache
        if ($this->cache !== null) {
            $this->cache->put($appDir, $this->routes);
        }

        $this->resolve();
    }

    /**
     * Resolve current route.
     */
    private function resolve(): void
    {
        $routeKey = $this->buildRouteKey();

        if ($routeKey === '') {
            $route = $this->routes['index'] ?? $this->routes['default'] ?? null;
        } else {
            $route = $this->routes[$routeKey] ?? null;
        }

        if ($route) {
            $this->applyRoute($route, $routeKey);
        } else {
            $this->resolveFromUrl();
        }

        if (empty($this->controller)) {
            $this->applyDefaultRoute();
        }
    }

    /**
     * Build route key from segments.
     */
    private function buildRouteKey(): string
    {
        if ($this->isAppRoute() && isset($this->segments[1])) {
            return $this->segments[1];
        }

        return implode('_', $this->segments);
    }

    /**
     * Check if current route is an app route.
     */
    private function isAppRoute(): bool
    {
        $firstSegment = $this->segments[0] ?? '';
        return $this->apps->hasApp($firstSegment);
    }

    /**
     * Apply found route.
     */
    private function applyRoute(array $route, string $routeKey): void
    {
        // Switch app if specified
        if (isset($route['app']) && $this->apps->hasApp($route['app'])) {
            $this->apps->switchApp($route['app']);
        }

        $this->module = $route['module'] ?? $this->defaults['module'];
        $this->controller = $route['controller'] ?? $routeKey;
        $this->action = $route['action'] ?? $this->defaults['action'];

        // Template overrides (use magic keys for backward compatibility)
        if (!empty($route['template'])) {
            $this->params['__template'] = $route['template'];
        }

        if (!empty($route['notemplate'])) {
            $this->params['__notemplate'] = true;
        }

        // Var remap
        if (!empty($route['var_remap'])) {
            $this->applyVarRemap($route['var_remap']);
        }
    }

    /**
     * Apply variable remap from route config.
     */
    private function applyVarRemap(array $remap): void
    {
        $offset = $this->isAppRoute() ? 2 : 1;
        $params = array_slice($this->segments, $offset);

        foreach ($remap as $i => $paramName) {
            $this->params[$paramName] = $params[$i] ?? '';
        }
    }

    /**
     * Resolve route from URL structure.
     */
    private function resolveFromUrl(): void
    {
        if (empty($this->segments)) {
            $this->applyDefaultRoute();
            return;
        }

        $segments = $this->getUrlSegments();

        // Check if module exists
        if (!$this->moduleResolver->moduleDirExists($segments['module'])) {
            $this->tryAsController($segments['module'], $segments['controller']);
            return;
        }

        $this->module = $segments['module'];
        $this->resolveController($segments);
    }

    /**
     * Get URL segments based on route type.
     */
    private function getUrlSegments(): array
    {
        if ($this->isAppRoute()) {
            return [
                'module' => $this->segments[1] ?? $this->defaults['module'],
                'controller' => $this->segments[2] ?? null,
                'action' => $this->segments[3] ?? $this->defaults['action'],
            ];
        }

        return [
            'module' => $this->segments[0] ?? $this->defaults['module'],
            'controller' => $this->segments[1] ?? null,
            'action' => $this->segments[2] ?? $this->defaults['action'],
        ];
    }

    /**
     * Try to resolve first segment as controller in default module.
     */
    private function tryAsController(string $potentialController, ?string $action): void
    {
        $defaultModule = $this->defaults['module'];

        if ($this->moduleResolver->controllerExists($defaultModule, $potentialController)) {
            $this->module = $defaultModule;
            $this->controller = $potentialController;
            $this->action = $action ?? $this->defaults['action'];
            return;
        }

        $this->resolve404();
    }

    /**
     * Resolve controller in module.
     */
    private function resolveController(array $segments): void
    {
        $module = $segments['module'];
        $controller = $segments['controller'];
        $action = $segments['action'];

        // Controller specified
        if ($controller) {
            if ($this->moduleResolver->controllerExists($module, $controller)) {
                $this->controller = $controller;
                $this->action = $action;
                return;
            }

            // Try as action in module/controller.php
            if ($this->moduleResolver->findFile("modules/{$module}/controller.php")) {
                $this->controller = $module;
                $this->action = $controller;
                return;
            }

            $this->resolve404();
            return;
        }

        // No controller - use module as controller
        $this->controller = $module;
        $this->action = $action;
    }

    /**
     * Resolve 404 route.
     */
    private function resolve404(): void
    {
        $module404 = $this->defaults['404_module'];
        $controller404 = $this->defaults['404_controller'];

        if ($this->moduleResolver->controllerExists($module404, $controller404)) {
            $this->module = $module404;
            $this->controller = $controller404;
            $this->action = $this->defaults['404_action'];
        }
        // If 404 not found, controller remains empty
    }

    /**
     * Apply default route from app config.
     */
    private function applyDefaultRoute(): void
    {
        $default = $this->apps->getDefaultRoute();
        $this->module = $default['module'];
        $this->controller = $default['controller'];
        $this->action = $default['action'];
    }

    // === Getters ===

    /**
     * Check if route is found.
     */
    public function hasRoute(): bool
    {
        return !empty($this->controller);
    }

    /**
     * Get URL segment by index.
     */
    public function segment(int $index, string $default = ''): string
    {
        return $this->segments[$index - 1] ?? $default;
    }

    /**
     * Get current path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get module name.
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * Get controller name.
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * Get action name.
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Get parameter by name.
     */
    public function getParam(string $name, string $default = ''): string
    {
        $value = $this->params[$name] ?? $default;
        return is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
    }
}
