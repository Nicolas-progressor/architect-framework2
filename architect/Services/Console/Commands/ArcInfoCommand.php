<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;

/**
 * Show information about the Architect project
 */
class ArcInfoCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'arc:info';
    protected string $description = 'Show information about the Architect project';

    public function execute(array $arguments, array $options): int
    {
        $this->output->line($this->output->header('Architect Framework'));
        $this->output->line();

        // PHP Version
        $phpVersion = PHP_VERSION;
        $this->output->line($this->output->info('PHP Version:') . ' ' . $phpVersion);

        // Project root
        $root = defined('ROOT_DIR') ? ROOT_DIR : getcwd();
        $this->output->line($this->output->info('Project Root:') . ' ' . $root);

        // App directory
        $appDir = defined('APP_DIR') ? APP_DIR : $root . '/app';
        $this->output->line($this->output->info('App Directory:') . ' ' . $appDir);

        // Environment
        $env = getenv('APP_ENV') ?: 'production';
        $this->output->line($this->output->info('Environment:') . ' ' . $env);

        // Debug mode
        $debug = defined('APP_DEBUG') && APP_DEBUG;
        $debugStatus = $debug
            ? $this->output->success('Enabled')
            : $this->output->error('Disabled');
        $this->output->line($this->output->info('Debug Mode:') . ' ' . $debugStatus);

        $this->output->line();

        // Check PHP version compatibility
        if (version_compare($phpVersion, '8.1', '>=')) {
            $this->success('PHP version is compatible (8.1+)');
        } else {
            $this->warning('PHP 8.1+ is required for full compatibility');
        }

        return 0;
    }
}
