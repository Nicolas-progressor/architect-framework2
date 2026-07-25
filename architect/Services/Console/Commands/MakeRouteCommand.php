<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;

/**
 * Create a new route
 */
class MakeRouteCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'make:route';
    protected string$description = 'Create a new route';

    public function getArguments(): array
    {
        return [
            ['name', 'Route name (e.g., users_list)', true],
        ];
    }

    public function getOptions(): array
    {
        return [
            ['--module', 'Module name (default: default module from router.json)'],
            ['--controller', 'Controller name'],
            ['--action', 'Action name (default: index)'],
            ['--app', 'Application name (default: home)'],
            ['--template', 'Template name'],
            ['--notemplate', 'Disable template for this route'],
            ['--global', 'Add to global routes instead of app routes'],
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $routeName = $arguments['name'];
        $module = $options['module'] ?? null;
        $controller = $options['controller'] ?? null;
        $action = $options['action'] ?? 'index';
        $app = $options['app'] ?? 'home';
        $template = $options['template'] ?? null;
        $noTemplate = $options['notemplate'] ?? false;
        $isGlobal = $options['global'] ?? false;

        // Validate route name
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $routeName)) {
            $this->error('Invalid route name. Use lowercase with underscores, e.g., users_list');
            return 1;
        }

        // Determine target file
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 4);
        $appDir = defined('APP_DIR') ? APP_DIR : $root . '/app';

        if ($isGlobal) {
            $routesPath = $appDir . '/routes/routes.json';
        } else {
            $routesPath = "{$appDir}/apps/{$app}/routes/routes.json";
        }

        // Load existing routes
        $routes = [];
        if (file_exists($routesPath)) {
            $data = json_decode(file_get_contents($routesPath), true);
            $routes = $data['routes'] ?? [];
        }

        // Check if route already exists
        if (isset($routes[$routeName])) {
            $this->error("Route {$routeName} already exists");
            return 1;
        }

        // Get defaults from router.json
        $routerConfigPath = $appDir . '/config/router.json';
        $routerConfig = [];
        if (file_exists($routerConfigPath)) {
            $routerConfig = json_decode(file_get_contents($routerConfigPath), true) ?? [];
        }

        // Build route config
        $routeConfig = [];

        if ($module) {
            $routeConfig['module'] = $module;
        }

        if ($controller) {
            $routeConfig['controller'] = $controller;
        }

        if ($action) {
            $routeConfig['action'] = $action;
        }

        if ($template) {
            $routeConfig['template'] = $template;
        }

        if ($noTemplate) {
            $routeConfig['notemplate'] = true;
        }

        if (!$isGlobal && $app !== 'home') {
            $routeConfig['app'] = $app;
        }

        // Add route
        $routes[$routeName] = $routeConfig;

        // Ensure directory exists
        $routesDir = dirname($routesPath);
        if (!is_dir($routesDir)) {
            mkdir($routesDir, 0o755, true);
        }

        // Save routes
        $output = [
            'default' => $routerConfig['default_action'] ?? 'index',
            'routes' => $routes,
        ];

        if (file_put_contents($routesPath, json_encode($output, JSON_PRETTY_PRINT)) === false) {
            $this->error("Failed to save routes to {$routesPath}");
            return 1;
        }

        $path = '/' . str_replace('_', '/', $routeName);
        $this->success("Route {$routeName} created successfully!");
        $this->line("  Path: {$path}");
        $this->line('  Handler: ' . ($routeConfig['module'] ?? 'default') . '/' . ($routeConfig['controller'] ?? 'default') . '@' . ($routeConfig['action'] ?? 'index'));

        return 0;
    }
}
