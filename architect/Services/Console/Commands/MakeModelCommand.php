<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;

/**
 * Create a new model class
 */
class MakeModelCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'make:model';
    protected string $description = 'Create a new model class';

    public function getArguments(): array
    {
        return [
            ['name', 'Model name (e.g., User)', true],
        ];
    }

    public function getOptions(): array
    {
        return [
            ['--module', 'Target module name'],
            ['--app', 'Target application name (default: home)'],
            ['--table', 'Database table name (optional, defaults to pluralized model name)'],
            ['--base', 'Extend ModelBase instead of Model'],
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $modelName = $arguments['name'];
        $module = $options['module'] ?? null;
        $app = $options['app'] ?? 'home';
        $table = $options['table'] ?? null;
        $useBase = $options['base'] ?? false;

        // Validate model name
        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $modelName)) {
            $this->error('Invalid model name. Use PascalCase, e.g., User, Post, Category');
            return 1;
        }

        // Determine target directory
        $basePath = defined('APP_DIR') ? APP_DIR : dirname(__DIR__, 4) . '/app';

        if ($module) {
            $targetDir = "{$basePath}/apps/{$app}/modules/{$module}/model";
        } else {
            $targetDir = "{$basePath}/apps/{$app}/modules/{$app}/model";
        }

        // Create directory if not exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0o755, true);
        }

        $filePath = "{$targetDir}/{$modelName}.php";

        // Check if file already exists
        if (file_exists($filePath)) {
            $this->error("Model {$modelName} already exists at {$filePath}");
            return 1;
        }

        // Determine table name
        $tableName = $table ?? $this->pluralize(strtolower($modelName));

        // Determine base class
        $baseClass = $useBase ? '\Architect\Services\Mvc\ModelBase' : '\Architect\Services\Mvc\Model';

        // Generate model content
        $content = $this->generateModel($modelName, $tableName, $baseClass);

        // Write file
        if (file_put_contents($filePath, $content) === false) {
            $this->error("Failed to create model file: {$filePath}");
            return 1;
        }

        $this->success("Model {$modelName} created successfully!");
        $this->line("  Path: {$filePath}");
        $this->line("  Table: {$tableName}");

        return 0;
    }

    /**
     * Generate model content
     */
    protected function generateModel(string $name, string $table, string $baseClass): string
    {
        $className = $name;
        $baseClassName = basename(str_replace('\\', '/', $baseClass));

        $template = <<<PHP
            <?php

            declare(strict_types=1);

            namespace app\home\modules\home\model;

            use {$baseClass};

            class {$className} extends {$baseClassName}
            {
                protected string \$table = '{$table}';
                protected string \$primaryKey = 'id';
                protected bool \$timestamps = true;

                /**
                 * The attributes that are mass assignable.
                 *
                 * @var array<int, string>
                 */
                protected array \$fillable = [
                    // 'name',
                    // 'email',
                ];

                /**
                 * The attributes that should be hidden for arrays.
                 *
                 * @var array<int, string>
                 */
                protected array \$hidden = [
                    // 'password',
                    // 'remember_token',
                ];

                /**
                 * The attributes that should be cast to native types.
                 *
                 * @var array<string, string>
                 */
                protected array \$casts = [
                    // 'email_verified_at' => 'datetime',
                    // 'created_at' => 'datetime',
                    // 'updated_at' => 'datetime',
                ];

                // Define relationships here
                // public function user(): HasOne
                // {
                //     return \$this->hasOne(User::class);
                // }
            }

            PHP;

        return $template;
    }

    /**
     * Simple pluralization (basic English rules)
     */
    protected function pluralize(string $word): string
    {
        // Irregular plurals
        $irregular = [
            'person' => 'people',
            'man' => 'men',
            'woman' => 'women',
            'child' => 'children',
            'tooth' => 'teeth',
            'foot' => 'feet',
            'mouse' => 'mice',
            'ox' => 'oxen',
        ];

        $lower = strtolower($word);
        if (isset($irregular[$lower])) {
            return $irregular[$lower];
        }

        // Words ending in 'y' preceded by consonant
        if (preg_match('/[^aeiou]y$/', $word)) {
            return substr($word, 0, -1) . 'ies';
        }

        // Words ending in 's', 'x', 'z', 'ch', 'sh'
        if (preg_match('/(s|x|z|ch|sh)$/', $word)) {
            return $word . 'es';
        }

        // Words ending in 'f' or 'fe'
        if (preg_match('/(f|fe)$/', $word)) {
            return preg_replace('/(f|fe)$/', 'ves', $word);
        }

        // Default: add 's'
        return $word . 's';
    }
}
