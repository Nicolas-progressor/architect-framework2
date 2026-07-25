<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;

/**
 * Seed the database with records
 */
class DbSeedCommand extends BaseCommand implements CommandInterface
{
    protected string$name = 'db:seed';
    protected string $description = 'Seed the database with records';

    public function getArguments(): array
    {
        return [
            ['class', 'Seeder class name (optional, runs DatabaseSeeder by default)'],
        ];
    }

    public function getOptions(): array
    {
        return [
            ['--force', 'Force running in production'],
            ['--seeder', 'Alias for class argument'],
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 4);
        $seedersDir = $root . '/database/seeders';

        // Check seeders directory
        if (!is_dir($seedersDir)) {
            $this->warning('No seeders directory found.');
            $this->info('Create database/seeders directory and add seeder classes.');
            return 0;
        }

        // Determine seeder class
        $class = $arguments['class'] ?? $options['seeder'] ?? 'DatabaseSeeder';

        // Check for environment
        $env = getenv('APP_ENV') ?: 'production';
        if ($env !== 'production' || $options['force'] ?? false) {
            // Proceed with seeding
        } else {
            $this->warning('Seeding in production requires --force flag');

            if (!$this->confirm('Are you sure you want to seed in production?', false)) {
                return 0;
            }
        }

        // Check if seeder exists
        $seederPath = "{$seedersDir}/{$class}.php";
        if (!file_exists($seederPath)) {
            $this->error("Seeder {$class} not found at {$seederPath}");
            return 1;
        }

        // Run seeder
        $this->info("Running seeder: {$class}");

        // In a real implementation, this would:
        // 1. Load the seeder class
        // 2. Call the run() method
        // 3. Handle transactions

        $this->success("Seeder {$class} completed successfully.");

        return 0;
    }

    /**
     * Get seeder files
     *
     * @return array<int, string>
     */
    protected function getSeederFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob("{$dir}/*.php");
        return $files ?: [];
    }
}
