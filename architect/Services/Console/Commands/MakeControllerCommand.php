<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;

/**
 * Create a new controller class
 */
class MakeControllerCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'make:controller';
    protected string $description = 'Create a new controller class';

    public function getArguments(): array
    {
        return [
            ['name', 'Controller name (e.g., UserController)', true],
        ];
    }

    public function getOptions(): array
    {
        return [
            ['--module', 'Target module name'],
            ['--app', 'Target application name (default: home)'],
            ['--resource', 'Create a resource controller with CRUD methods'],
            ['--api', 'Create an API controller (without template)'],
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $controllerName = $arguments['name'];
        $module = $options['module'] ?? null;
        $app = $options['app'] ?? 'home';
        $isResource = $options['resource'] ?? false;
        $isApi = $options['api'] ?? false;

        // Validate controller name
        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*Controller$/', $controllerName)) {
            $this->error('Invalid controller name. Use PascalCase, e.g., UserController');
            return 1;
        }

        // Determine target directory
        $basePath = defined('APP_DIR') ? APP_DIR : dirname(__DIR__, 4) . '/app';

        if ($module) {
            $targetDir = "{$basePath}/apps/{$app}/modules/{$module}/controller";
        } else {
            $targetDir = "{$basePath}/apps/{$app}/modules/{$app}/controller";
        }

        // Create directory if not exists
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0o755, true)) {
                $this->error("Failed to create directory: {$targetDir}");
                return 1;
            }
        }

        $filePath = "{$targetDir}/{$controllerName}.php";

        // Check if file already exists
        if (file_exists($filePath)) {
            $this->error("Controller {$controllerName} already exists at {$filePath}");
            return 1;
        }

        // Generate controller content
        $content = $this->generateController($controllerName, $module, $isResource, $isApi, $app);

        // Write file
        if (file_put_contents($filePath, $content) === false) {
            $this->error("Failed to create controller file: {$filePath}");
            return 1;
        }

        $this->success("Controller {$controllerName} created successfully!");
        $this->line("  Path: {$filePath}");

        return 0;
    }

    /**
     * Generate controller content
     */
    protected function generateController(
        string $name,
        ?string $module,
        bool $isResource,
        bool $isApi,
        string $app = 'home'
    ): string {
        $moduleName = $module ?? $app;
        $className = $name;

        $methods = $this->generateMethods($isResource, $isApi);

        $template = sprintf(
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace app\\%s\\modules\\%s\\controller;\n\nuse pattern\\controller;\n\nclass %s extends controller\n{\n%s}\n",
            $app,
            $moduleName,
            $className,
            $methods
        );

        return $template;
    }

    /**
     * Generate controller methods
     */
    protected function generateMethods(bool $isResource, bool $isApi): string
    {
        if ($isResource) {
            return $this->generateResourceMethods();
        }

        if ($isApi) {
            return $this->generateApiMethods();
        }

        return $this->generateDefaultMethods();
    }

    /**
     * Generate default index method
     */
    protected function generateDefaultMethods(): string
    {
        return <<<'PHP'
                public function index_app_output(): void
                {
                    $this->render('index');
                }
            PHP;
    }

    /**
     * Generate resource methods (CRUD)
     */
    protected function generateResourceMethods(): string
    {
        return <<<'PHP'
                public function index_app_output(): void
                {
                    // List all resources
                    $this->render('index');
                }

                public function view_app_output(): void
                {
                    $id = $this->param('id');
                    // View single resource
                    $this->render('view');
                }

                public function create_app_output(): void
                {
                    // Show create form
                    $this->render('create');
                }

                public function store_app_output(): void
                {
                    // Store new resource
                    $this->redirect('/resource');
                }

                public function edit_app_output(): void
                {
                    $id = $this->param('id');
                    // Show edit form
                    $this->render('edit');
                }

                public function update_app_output(): void
                {
                    $id = $this->param('id');
                    // Update resource
                    $this->redirect('/resource/' . $id);
                }

                public function destroy_app_output(): void
                {
                    $id = $this->param('id');
                    // Delete resource
                    $this->redirect('/resource');
                }
            PHP;
    }

    /**
     * Generate API methods (JSON responses)
     */
    protected function generateApiMethods(): string
    {
        return <<<'PHP'
                public function index_app_output(): void
                {
                    // Return JSON response
                    header('Content-Type: application/json');
                    echo json_encode(['data' => []]);
                }

                public function store_app_output(): void
                {
                    // Create resource and return JSON
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'data' => []]);
                }

                public function update_app_output(): void
                {
                    $id = $this->param('id');
                    // Update and return JSON
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                }

                public function destroy_app_output(): void
                {
                    $id = $this->param('id');
                    // Delete and return JSON
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                }
            PHP;
    }
}
