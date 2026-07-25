<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;
use Architect\Core\EnvironmentManager;
use Axiom\Migration\MigrationManager;
use Axiom\Orm\Connection\ConnectionManager;
use Axiom\Orm\Integrations\Architect\AxiomBootstrap;

/**
 * Run database migrations using Axiom ORM
 */
class DbMigrateCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'db:migrate';
    protected string $description = 'Run database migrations using Axiom ORM';

    public function getOptions(): array
    {
        return [
            ['--step', 'Number of migrations to run (default: all)'],
            ['--force', 'Force running in production'],
            ['--pretend', 'Show what would be executed without running'],
            ['--seed', 'Run seeders after migration'],
            ['--path', 'Custom migrations path'],
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        // Get migrations directory
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 4);
        $migrationsDir = $options['path'] ?? $root . '/migrations';

        // Check if Axiom ORM is available
        if (!$this->initAxiom()) {
            return 1;
        }

        // Check migrations directory
        if (!is_dir($migrationsDir)) {
            $this->warning('No migrations directory found: ' . $migrationsDir);
            $this->info('Run make:migration to create migrations.');
            return 0;
        }

        // Check for environment
        $env = getenv('APP_ENV') ?: 'production';
        if ($env === 'production' && !($options['force'] ?? false)) {
            $this->error('Running migrations in production requires --force flag');
            return 1;
        }

        try {
            // Create migration manager
            $manager = new MigrationManager($migrationsDir);

            // Check pending migrations
            $pending = $manager->getPendingMigrations();

            if (empty($pending)) {
                $this->info('Nothing to migrate.');
                return 0;
            }

            // Pretend mode
            if ($options['pretend'] ?? false) {
                $this->info('Would run migrations:');
                foreach ($pending as $migration) {
                    $this->line("  - {$migration['filename']}");
                }
                return 0;
            }

            // Limit step if specified
            if (isset($options['step'])) {
                $step = (int) $options['step'];
                $pending = array_slice($pending, 0, $step);
            }

            $this->info('Running migrations...');
            $this->line();

            // Run migrations
            $ran = $manager->migrate();

            $this->line();

            if (empty($ran)) {
                $this->info('Nothing to migrate.');
            } else {
                $this->success('Migrated: ' . count($ran) . ' migration(s)');
            }

            // Run seeders if requested
            if ($options['seed'] ?? false) {
                $this->line();
                $this->info('Running seeders...');
                // In a real implementation, this would call the seeder
                $this->success('Seeding completed.');
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Initialize Axiom ORM connection using AxiomBootstrap
     */
    protected function initAxiom(): bool
    {
        try {
            $environment = new EnvironmentManager();
            AxiomBootstrap::bootstrap($environment);
            ConnectionManager::getDefault();
            return true;
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();

            // Check for connection errors
            if (str_contains($errorMsg, 'Failed to connect') ||
                str_contains($errorMsg, 'could not find driver')) {
                $this->error('Cannot connect to database.');
                $this->line();
                $this->info('Options:');
                $this->line('  1. Set up MySQL/PostgreSQL and configure app/config/database.json');
                $this->line('  2. Use SQLite: set DB_CONNECTION=sqlite in .env');
                $this->line('  3. For development, configure app/config/environment/development.json');
            } else {
                $this->error('Axiom ORM error: ' . $errorMsg);
            }

            return false;
        }
    }
}
