<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;

/**
 * Optimize Composer autoload
 */
class OptimizeAutoloadCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'optimize:autoload';
    protected string $description = 'Optimize Composer autoload files';

    public function getOptions(): array
    {
        return [
            ['--no-dev', 'Exclude dev dependencies'],
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 4);

        // Check for composer.json
        $composerPath = $root . '/composer.json';
        if (!file_exists($composerPath)) {
            $this->error('composer.json not found');
            return 1;
        }

        $this->info('Optimizing Composer autoload...');

        // Build autoload
        $cmd = 'cd ' . escapeshellarg($root) . ' && composer dump-autoload';

        if ($options['no-dev'] ?? false) {
            $cmd .= ' --no-dev';
        }

        // Add flags for optimization
        $cmd .= ' --optimize';

        // Execute command
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $root);

        if (!is_resource($process)) {
            $this->error('Failed to run composer');
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

        if ($exitCode !== 0) {
            $this->error('Composer failed:');
            $this->line($error);
            return 1;
        }

        $this->success('Autoload optimized successfully!');
        $this->line($output);

        return 0;
    }
}
