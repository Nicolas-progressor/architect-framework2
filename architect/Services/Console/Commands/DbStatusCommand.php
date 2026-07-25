<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;
use Axiom\Orm\Connection\ConnectionManager;
use Axiom\Orm\Integrations\Architect\AxiomBootstrap;
use Axiom\Migration\MigrationManager;

/**
 * Show migration status using Axiom ORM
 */
class DbStatusCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'db:status';
    protected string $description = 'Show the status of all migrations using Axiom ORM';

    public function getOptions(): array
    {
        return [
            ['--path', 'Custom migrations path'],
            ['--verbose', 'Show detailed information'],
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

        try {
            // Create migration manager
            $manager = new MigrationManager($migrationsDir);
            
            // Get status
            $status = $manager->status();
            
            if (empty($status)) {
                $this->info('No migrations found.');
                return 0;
            }

            // Count stats
            $ran = count(array_filter($status, fn($s) => $s['ran']));
            $pending = count(array_filter($status, fn($s) => !$s['ran']));

            $this->line($this->output->header('Migration Status'));
            $this->line();
            $this->line("  Ran:     " . $this->output->success((string)$ran));
            $this->line("  Pending: " . $this->output->warning((string)$pending));
            $this->line();

            // Show detailed list
            $this->output->line($this->output->info('Migrations:'));
            
            $rows = [];
            foreach ($status as $s) {
                $statusIcon = $s['ran'] ? $this->output->success('✓') : $this->output->warning('○');
                $rows[] = [
                    $statusIcon,
                    $s['filename']
                ];
            }

            $this->output->table(['Status', 'Migration'], $rows);

            return 0;
        } catch (\Throwable $e) {
            $this->error('Failed to get status: ' . $e->getMessage());
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
