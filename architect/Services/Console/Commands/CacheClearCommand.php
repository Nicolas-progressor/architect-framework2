<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;

/**
 * Clear application cache
 */
class CacheClearCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'cache:clear';
    protected string $description = 'Clear application cache';

    public function getOptions(): array
    {
        return [
            ['--all', 'Clear all cache types'],
            ['--config', 'Clear config cache'],
            ['--routes', 'Clear routes cache'],
            ['--views', 'Clear views cache'],
            ['--console', 'Clear console commands cache'],
            ['--blueprint', 'Clear Blueprint templates cache'],
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 4);
        $bootstrapDir = $root . '/bootstrap/cache';

        $cleared = [];
        $errors = [];

        // Clear all if --all specified or no specific option
        $clearAll = $options['all'] ?? false;
        $clearConfig = $options['config'] ?? false;
        $clearRoutes = $options['routes'] ?? false;
        $clearViews = $options['views'] ?? false;
        $clearConsole = $options['console'] ?? false;
        $clearBlueprint = $options['blueprint'] ?? false;

        if (!$clearAll && !$clearConfig && !$clearRoutes && !$clearViews && !$clearConsole && !$clearBlueprint) {
            $clearAll = true;
        }

        if ($clearAll || $clearConfig) {
            $result = $this->clearCacheDir($bootstrapDir, 'config');
            if ($result === true) {
                $cleared[] = 'Config cache';
            } elseif ($result === false) {
                $errors[] = 'Config cache';
            }
        }

        if ($clearAll || $clearRoutes) {
            $result = $this->clearCacheDir($bootstrapDir, 'routes');
            if ($result === true) {
                $cleared[] = 'Routes cache';
            } elseif ($result === false) {
                $errors[] = 'Routes cache';
            }
        }

        if ($clearAll || $clearConsole) {
            $result = $this->clearCacheDir($bootstrapDir, 'console');
            if ($result === true) {
                $cleared[] = 'Console commands cache';
            } elseif ($result === false) {
                $errors[] = 'Console commands cache';
            }
        }

        if ($clearAll || $clearViews) {
            $appDir = defined('APP_DIR') ? APP_DIR : $root . '/app';
            $viewsDir = $appDir . '/home/modules/home/view/cache';

            if (is_dir($viewsDir)) {
                $this->deleteDirectory($viewsDir);
                $cleared[] = 'Views cache';
            }
        }

        if ($clearAll || $clearBlueprint) {
            $blueprintDir = $root . '/cache/blueprints';

            if (is_dir($blueprintDir)) {
                $this->deleteDirectory($blueprintDir);
                mkdir($blueprintDir, 0755, true);
                $cleared[] = 'Blueprint cache';
            }
        }

        // Output results
        if (!empty($cleared)) {
            $this->success('Cache cleared successfully:');
            foreach ($cleared as $item) {
                $this->line("  - {$item}");
            }
        }

        if (!empty($errors)) {
            $this->warning('Failed to clear some cache:');
            foreach ($errors as $item) {
                $this->line("  - {$item}");
            }
        }

        if (empty($cleared) && empty($errors)) {
            $this->info('No cache to clear.');
        }

        return empty($errors) ? 0 : 1;
    }

    /**
     * Clear cache directory
     */
    protected function clearCacheDir(string $baseDir, string $type): ?bool
    {
        $dir = "{$baseDir}/{$type}";

        if (!is_dir($dir)) {
            return null;
        }

        if ($this->deleteDirectory($dir)) {
            mkdir($dir, 0755, true);
            return true;
        }

        return false;
    }

    /**
     * Recursively delete directory
     */
    protected function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $items = array_diff(scandir($dir), ['.', '..']);

        foreach ($items as $item) {
            $path = "{$dir}/{$item}";

            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }
}
