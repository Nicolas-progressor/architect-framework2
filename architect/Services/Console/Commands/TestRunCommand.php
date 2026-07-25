<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;

/**
 * Run application tests
 */
class TestRunCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'test:run';
    protected string $description = 'Run application tests';

    public function getOptions(): array
    {
        return [
            ['--filter', 'Filter tests by name'],
            ['--group', 'Run tests by group'],
            ['--exclude-group', 'Exclude tests by group'],
            ['--verbose', 'Show verbose output'],
            ['--coverage', 'Generate code coverage report'],
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 4);
        $testsDir = $root . '/tests';

        // Check tests directory
        if (!is_dir($testsDir)) {
            $this->warning('No tests directory found.');
            $this->info('Create a tests directory to run tests.');
            return 0;
        }

        // Build PHPUnit command
        $phpunit = $this->findPhpUnit($root);

        if (!$phpunit) {
            $this->error('PHPUnit not found.');
            $this->info('Install PHPUnit: composer require --dev phpunit/phpunit');
            return 1;
        }

        // Build command
        $cmd = $phpunit;

        if ($options['filter'] ?? false) {
            $cmd .= ' --filter=' . escapeshellarg($options['filter']);
        }

        if ($options['group'] ?? false) {
            $cmd .= ' --group=' . escapeshellarg($options['group']);
        }

        if ($options['exclude-group'] ?? false) {
            $cmd .= ' --exclude-group=' . escapeshellarg($options['exclude-group']);
        }

        if ($options['verbose'] ?? $options['v'] ?? false) {
            $cmd .= ' --verbose';
        }

        if ($options['coverage'] ?? false) {
            $cmd .= ' --coverage-html coverage';
        }

        $this->info('Running tests...');

        // Execute command
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $root);

        if (!is_resource($process)) {
            $this->error('Failed to run tests');
            return 1;
        }

        // Read output
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);

        // Close pipes
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        // Get exit code
        $exitCode = proc_close($process);

        // Display output
        echo $output;

        if ($error) {
            $this->error($error);
        }

        // Summary
        if ($exitCode === 0) {
            $this->success('All tests passed!');
        } else {
            $this->error('Some tests failed.');
        }

        return $exitCode;
    }

    /**
     * Find PHPUnit binary
     */
    protected function findPhpUnit(string $root): ?string
    {
        // Check vendor/bin/phpunit
        $vendorPath = $root . '/vendor/bin/phpunit';
        if (file_exists($vendorPath)) {
            return $vendorPath;
        }

        // Check global phpunit
        $globalPath = exec('which phpunit');
        if ($globalPath && file_exists($globalPath)) {
            return $globalPath;
        }

        return null;
    }
}
