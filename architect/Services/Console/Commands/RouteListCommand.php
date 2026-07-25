<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;

/**
 * List all registered routes
 */
class RouteListCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'route:list';
    protected string $description = 'List all registered routes';

    public function getOptions(): array
    {
        return [
            ['--app', 'Filter by application name'],
            ['--module', 'Filter by module name'],
            ['--verbose', 'Show detailed information'],
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 4);
        $appDir = defined('APP_DIR') ? APP_DIR : $root . '/app';

        $routes = [];

        // Load global routes
        $globalRoutesPath = $appDir . '/routes/routes.json';
        if (file_exists($globalRoutesPath)) {
            $globalRoutes = json_decode(file_get_contents($globalRoutesPath), true);
            if ($globalRoutes && isset($globalRoutes['routes'])) {
                $routes = array_merge($routes, $this->parseRoutes($globalRoutes['routes'], 'global'));
            }
        }

        // Load app-specific routes
        $configPath = $appDir . '/config/apps.json';
        $apps = ['home']; // Default app

        if (file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath), true);
            if ($config && isset($config['apps'])) {
                $apps = array_merge($apps, $config['apps']);
            }
        }

        // Filter by app if specified
        $filterApp = $options['app'] ?? null;
        if ($filterApp) {
            $apps = [$filterApp];
        }

        foreach ($apps as $app) {
            $appRoutesPath = "{$appDir}/{$app}/routes/routes.json";
            if (file_exists($appRoutesPath)) {
                $appRoutes = json_decode(file_get_contents($appRoutesPath), true);
                if ($appRoutes && isset($appRoutes['routes'])) {
                    $parsed = $this->parseRoutes($appRoutes['routes'], $app);
                    $routes = array_merge($routes, $parsed);
                }
            }
        }

        // Filter by module if specified
        $filterModule = $options['module'] ?? null;
        if ($filterModule) {
            $routes = array_filter($routes, fn($r) => $r['module'] === $filterModule);
        }

        if (empty($routes)) {
            $this->warning('No routes found.');
            return 0;
        }

        // Display routes
        $this->output->line($this->output->header('Registered Routes:'));
        $this->output->line();

        $verbose = $options['verbose'] ?? false;

        if ($verbose) {
            $this->table(
                ['Name', 'Module', 'Controller', 'Action', 'Template', 'App'],
                array_map(fn($r) => [
                    $r['name'],
                    $r['module'],
                    $r['controller'],
                    $r['action'],
                    $r['template'] ?? '-',
                    $r['app'],
                ], $routes)
            );
        } else {
            $this->table(
                ['Name', 'Path', 'Handler'],
                array_map(fn($r) => [
                    $r['name'],
                    $r['path'],
                    "{$r['module']}/{$r['controller']}@{$r['action']}",
                ], $routes)
            );
        }

        $this->output->line();
        $this->info('Total routes: ' . count($routes));

        return 0;
    }

    /**
     * Parse routes from config
     *
     * @param array<string, array> $routesConfig
     * @param string $app
     * @return array<int, array{name: string, path: string, module: string, controller: string, action: string, template: ?string, app: string}>
     */
    protected function parseRoutes(array $routesConfig, string $app): array
    {
        $routes = [];
        $routerConfig = [];

        // Load router config for defaults
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 4);
        $routerConfigPath = (defined('APP_DIR') ? APP_DIR : $root . '/app') . '/config/router.json';
        if (file_exists($routerConfigPath)) {
            $routerConfig = json_decode(file_get_contents($routerConfigPath), true) ?? [];
        }

        $defaultModule = $routerConfig['default_module'] ?? 'home';
        $defaultController = $routerConfig['default_controller'] ?? 'home';
        $defaultAction = $routerConfig['default_action'] ?? 'index';

        foreach ($routesConfig as $name => $config) {
            $module = $config['module'] ?? $defaultModule;
            $controller = $config['controller'] ?? $defaultController;
            $action = $config['action'] ?? $defaultAction;

            // Build path from route name
            $path = '/' . str_replace('_', '/', $name);

            $routes[] = [
                'name' => $name,
                'path' => $path,
                'module' => $module,
                'controller' => $controller,
                'action' => $action,
                'template' => $config['template'] ?? null,
                'app' => $config['app'] ?? $app,
            ];
        }

        return $routes;
    }
}
