<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;

/**
 * Create a new module
 */
class MakeModuleCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'make:module';
    protected string $description = 'Create a new module with controller, model, and view';

    public function getArguments(): array
    {
        return [
            ['name', 'Module name (e.g., blog)', true],
        ];
    }

    public function getOptions(): array
    {
        return [
            ['--app', 'Target application name (default: home)'],
            ['--resource', 'Create with resource controller (CRUD methods)'],
            ['--api', 'Create API module (JSON responses)'],
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $moduleName = $arguments['name'];
        $app = $options['app'] ?? 'home';
        $isResource = $options['resource'] ?? false;
        $isApi = $options['api'] ?? false;

        // Validate module name
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $moduleName)) {
            $this->error('Invalid module name. Use lowercase with underscores, e.g., blog_post, products');
            return 1;
        }

        // Determine target directory
        $basePath = defined('APP_DIR') ? APP_DIR : dirname(__DIR__, 4) . '/app';
        $moduleDir = "{$basePath}/apps/{$app}/modules/{$moduleName}";

        // Check if module already exists
        if (is_dir($moduleDir)) {
            $this->error("Module {$moduleName} already exists at {$moduleDir}");
            return 1;
        }

        // Create module structure
        $this->info("Creating module: {$moduleName}");

        // Create directories
        $dirs = [
            'controller',
            'model',
            'view',
            'lang',
        ];

        foreach ($dirs as $dir) {
            $path = "{$moduleDir}/{$dir}";
            if (!mkdir($path, 0o755, true)) {
                $this->error("Failed to create directory: {$path}");
                return 1;
            }
        }

        // Create controller
        $controllerName = ucfirst($moduleName) . 'Controller';
        $controllerPath = "{$moduleDir}/controller/{$controllerName}.php";

        $controllerContent = $this->generateController($moduleName, $controllerName, $isResource, $isApi);
        file_put_contents($controllerPath, $controllerContent);

        // Create model
        $modelName = ucfirst($moduleName);
        $modelPath = "{$moduleDir}/model/{$modelName}.php";
        $tableName = $this->pluralize($moduleName);

        $modelContent = $this->generateModel($modelName, $tableName);
        file_put_contents($modelPath, $modelContent);

        // Create view
        $viewPath = "{$moduleDir}/view/index.php";
        $viewContent = $this->generateView($moduleName);
        file_put_contents($viewPath, $viewContent);

        $this->success("Module {$moduleName} created successfully!");
        $this->line("  Directory: {$moduleDir}");
        $this->line("  Controller: {$controllerPath}");
        $this->line("  Model: {$modelPath}");
        $this->line("  View: {$viewPath}");

        return 0;
    }

    /**
     * Generate controller content
     */
    protected function generateController(
        string $module,
        string $name,
        bool $isResource,
        bool $isApi
    ): string {
        $methods = $isResource
            ? $this->generateResourceMethods()
            : $this->generateDefaultMethods($isApi);

        $template = <<<PHP
            <?php

            declare(strict_types=1);

            namespace app\home\modules\{$module}\controller;

            use pattern\controller;

            class {$name} extends controller
            {
            {$methods}
            }

            PHP;

        return $template;
    }

    /**
     * Generate default methods
     */
    protected function generateDefaultMethods(bool $isApi): string
    {
        if ($isApi) {
            return <<<'PHP'
                    public function index_app_output(): void
                    {
                        header('Content-Type: application/json');
                        echo json_encode(['data' => []]);
                    }
                PHP;
        }

        return <<<'PHP'
                public function index_app_output(): void
                {
                    $this->render('index');
                }
            PHP;
    }

    /**
     * Generate resource methods
     */
    protected function generateResourceMethods(): string
    {
        return <<<'PHP'
                public function index_app_output(): void
                {
                    $this->render('index');
                }

                public function view_app_output(): void
                {
                    $id = $this->param('id');
                    $this->render('view');
                }

                public function create_app_output(): void
                {
                    $this->render('create');
                }

                public function store_app_output(): void
                {
                    $this->redirect('/' . $this->segment(1));
                }

                public function edit_app_output(): void
                {
                    $id = $this->param('id');
                    $this->render('edit');
                }

                public function update_app_output(): void
                {
                    $id = $this->param('id');
                    $this->redirect('/' . $this->segment(1) . '/' . $id);
                }

                public function destroy_app_output(): void
                {
                    $id = $this->param('id');
                    $this->redirect('/' . $this->segment(1));
                }
            PHP;
    }

    /**
     * Generate model content
     */
    protected function generateModel(string $name, string $table): string
    {
        $template = <<<PHP
            <?php

            declare(strict_types=1);

            namespace app\home\modules\{$name}\model;

            use Architect\Services\Mvc\ModelBase;

            class {$name} extends ModelBase
            {
                protected string \$table = '{$table}';
                protected string \$primaryKey = 'id';
                protected bool \$timestamps = true;

                /** @var array<int, string> */
                protected array \$fillable = [];

                /** @var array<int, string> */
                protected array \$hidden = [];

                /** @var array<string, string> */
                protected array \$casts = [];
            }

            PHP;

        return $template;
    }

    /**
     * Generate view content
     */
    protected function generateView(string $module): string
    {
        $title = ucwords(str_replace('_', ' ', $module));

        $template = <<<HTML
            <div class="container">
                <h1>{$title}</h1>

                <p>Module content goes here.</p>
            </div>
            HTML;

        return $template;
    }

    /**
     * Simple pluralization
     */
    protected function pluralize(string $word): string
    {
        $irregular = [
            'person' => 'people',
            'man' => 'men',
            'woman' => 'women',
            'child' => 'children',
        ];

        $lower = strtolower($word);
        if (isset($irregular[$lower])) {
            return $irregular[$lower];
        }

        if (preg_match('/[^aeiou]y$/', $word)) {
            return substr($word, 0, -1) . 'ies';
        }

        if (preg_match('/(s|x|z|ch|sh)$/', $word)) {
            return $word . 'es';
        }

        return $word . 's';
    }
}
