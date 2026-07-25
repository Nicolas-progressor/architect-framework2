<?php

declare(strict_types=1);

namespace Axiom\Migration\Commands;

use Axiom\Migration\MigrationManager;

/**
 * Base command for migrations
 */
abstract class BaseCommand
{
    protected MigrationManager $manager;
    protected array $options = [];

    public function __construct(array $options = [])
    {
        $this->options = $options;
        $path = $options['path'] ?? dirname(__DIR__, 2) . '/migrations';
        $table = $options['table'] ?? 'migrations';
        
        $this->manager = new MigrationManager($path, $table);
    }

    /**
     * Run the command
     */
    abstract public function run(array $args): int;

    /**
     * Print message
     */
    protected function info(string $message): void
    {
        echo $message . "\n";
    }

    /**
     * Print error
     */
    protected function error(string $message): void
    {
        echo "ERROR: {$message}\n";
    }

    /**
     * Print success
     */
    protected function success(string $message): void
    {
        echo "✓ {$message}\n";
    }
}

/**
 * Migrate command
 */
class MigrateCommand extends BaseCommand
{
    public function run(array $args): int
    {
        $pretend = in_array('--pretend', $args) || in_array('-p', $args);
        
        $this->info("Running migrations...");
        
        try {
            $ran = $this->manager->migrate($pretend);
            
            if (empty($ran)) {
                $this->info("Nothing to migrate.");
                return 0;
            }
            
            $this->success(sprintf("Migrated %d migration(s).", count($ran)));
            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}

/**
 * Rollback command
 */
class RollbackCommand extends BaseCommand
{
    public function run(array $args): int
    {
        $pretend = in_array('--pretend', $args) || in_array('-p', $args);
        
        $this->info("Rolling back migrations...");
        
        try {
            $rolledBack = $this->manager->rollback($pretend);
            
            if (empty($rolledBack)) {
                $this->info("Nothing to rollback.");
                return 0;
            }
            
            $this->success(sprintf("Rolled back %d migration(s).", count($rolledBack)));
            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}

/**
 * Reset command
 */
class ResetCommand extends BaseCommand
{
    public function run(array $args): int
    {
        $pretend = in_array('--pretend', $args) || in_array('-p', $args);
        
        $this->info("Resetting all migrations...");
        
        try {
            $reset = $this->manager->reset($pretend);
            
            if (empty($reset)) {
                $this->info("Nothing to reset.");
                return 0;
            }
            
            $this->success(sprintf("Reset %d migration(s).", count($reset)));
            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}

/**
 * Status command
 */
class StatusCommand extends BaseCommand
{
    public function run(array $args): int
    {
        $status = $this->manager->status();
        
        if (empty($status)) {
            $this->info("No migrations found.");
            return 0;
        }
        
        $this->info("\nMigration Status:\n");
        $this->info(str_repeat('-', 60));
        
        foreach ($status as $migration) {
            $icon = $migration['ran'] ? '✓' : '○';
            $status = $migration['ran'] ? 'Ran' : 'Pending';
            $this->info(sprintf("%s %-40s %s", $icon, $migration['filename'], $status));
        }
        
        $this->info(str_repeat('-', 60));
        
        $ran = count(array_filter($status, fn($m) => $m['ran']));
        $pending = count($status) - $ran;
        
        $this->info("\nRan: {$ran}, Pending: {$pending}\n");
        
        return 0;
    }
}

/**
 * Make migration command
 */
class MakeMigrationCommand extends BaseCommand
{
    public function run(array $args): int
    {
        if (empty($args[0] ?? null)) {
            $this->error("Migration name is required.");
            $this->info("Usage: axiom:migration:make <name>");
            return 1;
        }

        $name = $args[0];
        $path = $this->manager->getPath();

        try {
            $filename = MigrationManager::create($name, $path);
            $this->success("Created migration: {$filename}");
            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}
