<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;
use Axiom\Migration\MigrationManager;
use Axiom\Orm\Connection\ConnectionManager;
use Axiom\Orm\Integrations\Architect\AxiomBootstrap;

/**
 * Rollback database migrations using Axiom ORM
 */
class DbRollbackCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'db:rollback';
    protected string $description = 'Rollback the last database migration using Axiom ORM';

    public function getOptions(): array
    {
        return [
            ['--step', 'Number of migrations to rollback (default: 1)'],
            ['--force', 'Force running in production'],
            ['--pretend', 'Show what would be rolled back without executing'],
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
            return 0;
        }

        // Check for environment
        $env = getenv('APP_ENV') ?: 'production';
        if ($env === 'production' && !($options['force'] ?? false)) {
            $this->error('Rolling back migrations in production requires --force flag');
            return 1;
        }

        try {
            // Create migration manager
            $manager = new MigrationManager($migrationsDir);

            // Get status to check if there are migrations to rollback
            $status = $manager->status();
            $ranCount = count(array_filter($status, fn($s) => $s['ran']));

            if ($ranCount === 0) {
                $this->info('Nothing to rollback.');
                return 0;
            }

            // Pretend mode
            if ($options['pretend'] ?? false) {
                $this->info('Would rollback migrations:');
                foreach (array_reverse($status) as $s) {
                    if ($s['ran']) {
                        $this->line("  - {$s['filename']}");
                    }
                }
                return 0;
            }

            $this->info('Rolling back migrations...');
            $this->line();

            // Rollback - note: step parameter is handled by batch in Axiom
            $rolledBack = $manager->rollback();

            $this->line();

            if (empty($rolledBack)) {
                $this->info('Nothing to rollback.');
            } else {
                $this->success('Rolled back: ' . count($rolledBack) . ' migration(s)');
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error('Rollback failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Initialize Axiom ORM connection using AxiomBootstrap
     */
    protected function initAxiom(): bool
    {
        try {
            AxiomBootstrap::bootstrap();
            ConnectionManager::getDefault();
            return true;
        } catch (\Throwable $e) {
            $this->error('Axiom ORM is not configured: ' . $e->getMessage());
            $this->info('Please create app/config/database.json or set DB_* environment variables.');
            return false;
        }
    }
}
