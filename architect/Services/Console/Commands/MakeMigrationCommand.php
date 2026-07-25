<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;

/**
 * Create a new database migration (Axiom ORM format)
 */
class MakeMigrationCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'make:migration';
    protected string $description = 'Create a new database migration (Axiom ORM format)';

    public function getArguments(): array
    {
        return [
            ['name', 'Migration name (e.g., create_users_table)', true],
        ];
    }

    public function getOptions(): array
    {
        return [
            ['--table', 'Table name (optional, inferred from migration name)'],
            ['--create', 'Indicate that the migration will create a table'],
            ['--modify', 'Indicate that the migration will modify an existing table'],
            ['--path', 'Custom migrations directory'],
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $migrationName = $arguments['name'];
        $table = $options['table'] ?? null;
        $isCreate = $options['create'] ?? false;
        $isModify = $options['modify'] ?? false;

        // Validate migration name
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $migrationName)) {
            $this->error('Invalid migration name. Use lowercase with underscores');
            return 1;
        }

        // Determine table name
        if (!$table) {
            $table = $this->extractTableName($migrationName);
        }

        // Determine target directory
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 4);
        $migrationsDir = $options['path'] ?? $root . '/migrations';

        // Create migrations directory if not exists
        if (!is_dir($migrationsDir)) {
            mkdir($migrationsDir, 0o755, true);
            $this->info('Created migrations directory: ' . $migrationsDir);
        }

        // Generate timestamp
        $timestamp = date('Y_m_d_His');

        // File name
        $fileName = "{$timestamp}_{$migrationName}.php";
        $filePath = "{$migrationsDir}/{$fileName}";

        // Check if file already exists
        if (file_exists($filePath)) {
            $this->error("Migration {$fileName} already exists");
            return 1;
        }

        // Generate migration content (Axiom ORM format)
        $content = $this->generateMigration($migrationName, $table, $isCreate, $isModify);

        // Write file
        if (file_put_contents($filePath, $content) === false) {
            $this->error("Failed to create migration file: {$filePath}");
            return 1;
        }

        $this->success('Migration created successfully!');
        $this->line("  File: {$fileName}");
        $this->line("  Table: {$table}");

        return 0;
    }

    /**
     * Extract table name from migration name
     */
    protected function extractTableName(string $name): string
    {
        // Remove common prefixes/suffixes
        $table = preg_replace('/^(create_|modify_|update_|delete_|add_|drop_)/', '', $name);
        $table = preg_replace('/_(table|column|index|key)$/', '', $table);

        return $this->pluralize($table);
    }

    /**
     * Generate migration content (Axiom ORM format)
     */
    protected function generateMigration(string $name, string $table, bool $isCreate, bool $isModify): string
    {
        $className = $this->toClassName($name);

        if ($isCreate) {
            $up = $this->generateCreateUp($table);
        } elseif ($isModify) {
            $up = $this->generateModifyUp($table);
        } else {
            $up = $this->generateEmptyUp($table);
        }

        $template = <<<PHP
            <?php

            declare(strict_types=1);

            use Axiom\Migration\Migration;
            use Axiom\Migration\Blueprint;

            class {$className} extends Migration
            {
                /**
                 * Run the migration
                 */
                public function up(): void
                {
            {$up}
                }

                /**
                 * Reverse the migration
                 */
                public function down(): void
                {
            {$this->generateDown($table, $isCreate, $isModify)}
                }
            }

            PHP;

        return $template;
    }

    /**
     * Generate up method for create table
     */
    protected function generateCreateUp(string $table): string
    {
        return <<<PHP
                    \$this->create('{$table}', function (Blueprint \$table) {
                        \$table->id();
                        \$table->string('name');
                        \$table->string('email')->unique();
                        \$table->string('password');
                        \$table->enum('status', ['active', 'inactive'])->default('active');
                        \$table->timestamps();
                    });
            PHP;
    }

    /**
     * Generate up method for modify table
     */
    protected function generateModifyUp(string $table): string
    {
        return <<<PHP
                    \$this->table('{$table}', function (Blueprint \$table) {
                        // Add column: \$table->string('column_name');
                        // Drop column: \$table->dropColumn('column_name');
                        // Rename column: \$table->renameColumn('old_name', 'new_name');
                        // Add index: \$table->index('column_name');
                        // Add unique: \$table->unique(['column1', 'column2']);
                    });
            PHP;
    }

    /**
     * Generate empty up method
     */
    protected function generateEmptyUp(string $table): string
    {
        return <<<PHP
                    // \$this->create('{$table}', function (Blueprint \$table) {
                    //     \$table->id();
                    //     // Add your columns here
                    // });

                    // Or modify existing table:
                    // \$this->table('{$table}', function (Blueprint \$table) {
                    //     \$table->string('new_column');
                    // });
            PHP;
    }

    /**
     * Generate down method
     */
    protected function generateDown(string $table, bool $isCreate, bool $isModify): string
    {
        if ($isCreate) {
            return "        \$this->drop('{$table}');";
        }

        return '        // Add rollback logic here';
    }

    /**
     * Convert snake_case to PascalCase
     */
    protected function toClassName(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    /**
     * Simple pluralization
     */
    protected function pluralize(string $word): string
    {
        // Already plural
        if (preg_match('/(s|x|z|ch|sh|eses)$/i', $word)) {
            return $word;
        }

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
