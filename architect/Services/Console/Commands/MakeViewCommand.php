<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;

/**
 * Create a new view template
 */
class MakeViewCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'make:view';
    protected string$description = 'Create a new view template';

    public function getArguments(): array
    {
        return [
            ['name', 'View name (e.g., users/index)', true],
        ];
    }

    public function getOptions(): array
    {
        return [
            ['--module', 'Target module name'],
            ['--app', 'Target application name (default: home)'],
            ['--type', 'View type: blade, php (default: php)'],
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $viewName = $arguments['name'];
        $module = $options['module'] ?? null;
        $app = $options['app'] ?? 'home';
        $type = $options['type'] ?? 'php';

        // Validate view name
        if (empty($viewName)) {
            $this->error('View name is required');
            return 1;
        }

        // Determine target directory
        $basePath = defined('APP_DIR') ? APP_DIR : dirname(__DIR__, 4) . '/app';

        if ($module) {
            $targetDir = "{$basePath}/apps/{$app}/modules/{$module}/view";
        } else {
            $targetDir = "{$basePath}/apps/{$app}/modules/{$app}/view";
        }

        // Handle nested views (e.g., users/index)
        $parts = explode('/', $viewName);
        if (count($parts) > 1) {
            $viewFile = array_pop($parts);
            $targetDir .= '/' . implode('/', $parts);
        } else {
            $viewFile = $viewName;
        }

        // Add extension if not present
        if (!str_ends_with($viewFile, '.php') && !str_ends_with($viewFile, '.blade.php')) {
            if ($type === 'blade') {
                $viewFile .= '.blade.php';
            } else {
                $viewFile .= '.php';
            }
        }

        // Create directory if not exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filePath = "{$targetDir}/{$viewFile}";

        // Check if file already exists
        if (file_exists($filePath)) {
            $this->error("View already exists at {$filePath}");
            return 1;
        }

        // Generate view content
        $content = $this->generateView($viewName);

        // Write file
        if (file_put_contents($filePath, $content) === false) {
            $this->error("Failed to create view file: {$filePath}");
            return 1;
        }

        $this->success("View {$viewName} created successfully!");
        $this->line("  Path: {$filePath}");

        return 0;
    }

    /**
     * Generate view content
     */
    protected function generateView(string $name): string
    {
        $title = ucwords(str_replace(['/', '_', '-'], ' ', $name));

        $template = <<<HTML
<div class="container">
    <h1>{$title}</h1>

    <p>Content goes here.</p>
</div>
HTML;

        return $template;
    }
}
