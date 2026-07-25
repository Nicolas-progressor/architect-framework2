<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;

/**
 * Create a new application
 */
class MakeAppCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'make:app';
    protected string $description = 'Create a new application';

    public function getArguments(): array
    {
        return [
            ['name', 'Application name (e.g., admin)', true],
        ];
    }

    public function getOptions(): array
    {
        return [
            ['--template', 'Template to use (default: bootstrap)'],
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $appName = $arguments['name'];
        $template = $options['template'] ?? 'bootstrap';

        // Validate app name
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $appName)) {
            $this->error('Invalid application name. Use lowercase with underscores, e.g., admin, blog');
            return 1;
        }

        // Determine target directory
        $basePath = defined('APP_DIR') ? APP_DIR : dirname(__DIR__, 4) . '/app';
        $appDir = "{$basePath}/apps/{$appName}";

        // Check if app already exists
        if (is_dir($appDir)) {
            $this->error("Application {$appName} already exists at {$appDir}");
            return 1;
        }

        $this->info("Creating application: {$appName}");

        // Create application structure
        $dirs = [
            'modules',
            'template',
            'config',
            'routes',
        ];

        foreach ($dirs as $dir) {
            $path = "{$appDir}/{$dir}";
            if (!mkdir($path, 0755, true)) {
                $this->error("Failed to create directory: {$path}");
                return 1;
            }
        }

        // Create appbootstrap.php
        $bootstrapPath = "{$appDir}/appbootstrap.php";
        $bootstrapContent = $this->generateBootstrap($appName);
        file_put_contents($bootstrapPath, $bootstrapContent);

        // Create default module
        $moduleDir = "{$appDir}/modules/{$appName}";
        mkdir("{$moduleDir}/controller", 0755, true);
        mkdir("{$moduleDir}/model", 0755, true);
        mkdir("{$moduleDir}/view", 0755, true);

        // Create default controller
        $controllerName = ucfirst($appName) . 'Controller';
        $controllerPath = "{$moduleDir}/controller/{$controllerName}.php";
        $controllerContent = $this->generateController($controllerName);
        file_put_contents($controllerPath, $controllerContent);

        // Create default view
        $viewPath = "{$moduleDir}/view/index.php";
        $viewContent = $this->generateView($appName);
        file_put_contents($viewPath, $viewContent);

        // Create config
        $configPath = "{$appDir}/config/template.json";
        $configContent = $this->generateConfig($template);
        file_put_contents($configPath, $configContent);

        // Add to apps.json
        $this->registerApp($appName);

        $this->success("Application {$appName} created successfully!");
        $this->line("  Directory: {$appDir}");
        $this->line("  Default module: {$appDir}/modules/{$appName}/");

        return 0;
    }

    /**
     * Generate appbootstrap.php content
     */
    protected function generateBootstrap(string $name): string
    {
        $className = ucfirst($name) . 'Bootstrap';

        return <<<PHP
<?php

declare(strict_types=1);

namespace app\{$name};

class {$className}
{
    /**
     * Bootstrap the application
     */
    public function bootstrap(): void
    {
        // Register routes, middleware, services, etc.
    }
}
PHP;
    }

    /**
     * Generate default controller
     */
    protected function generateController(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace app\home\modules\home\controller;

use pattern\controller;

class {$name} extends controller
{
    public function index_app_output(): void
    {
        \$this->render('index');
    }
}
PHP;
    }

    /**
     * Generate default view
     */
    protected function generateView(string $name): string
    {
        $title = ucwords(str_replace('_', ' ', $name));

        return <<<HTML
<div class="container">
    <h1>{$title}</h1>
    <p>Welcome to your new application!</p>
</div>
HTML;
    }

    /**
     * Generate config
     */
    protected function generateConfig(string $template): string
    {
        return json_encode([
            'template' => $template,
            'layout' => 'main',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Register app in apps.json
     */
    protected function registerApp(string $name): bool
    {
        $basePath = defined('APP_DIR') ? APP_DIR : dirname(__DIR__, 4) . '/app';
        $configPath = "{$basePath}/config/apps.json";

        if (!file_exists($configPath)) {
            $config = [];
        } else {
            $config = json_decode(file_get_contents($configPath), true) ?? [];
        }

        if (!isset($config['apps'])) {
            $config['apps'] = [];
        }

        if (!in_array($name, $config['apps'])) {
            $config['apps'][] = $name;
        }

        return file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT)) !== false;
    }
}
