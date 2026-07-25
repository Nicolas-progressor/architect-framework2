<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;
use Architect\Helpers\Core\HelperDiscovery;

/**
 * Cache helpers discovery
 */
class HelpersCacheCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'helpers:cache';
    protected string $description = 'Cache helpers discovery for better performance';

    public function getOptions(): array
    {
        return [
            ['--force', 'Force cache regeneration even if cache exists'],
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 4);
        $cacheDir = $root . '/bootstrap/cache';

        // Create cache directory
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $force = $options['force'] ?? false;
        $cacheFile = $cacheDir . '/helpers.php';

        if (!$force && file_exists($cacheFile)) {
            $this->info('Helpers cache already exists. Use --force to regenerate.');
            return 0;
        }

        $discovery = new HelperDiscovery();
        // Temporarily disable cache usage to force fresh discovery
        $discovery->setUseCache(false);
        $helpers = $discovery->discover();

        $content = '<?php' . PHP_EOL . PHP_EOL;
        $content .= 'return ' . var_export($helpers, true) . ';';

        if (file_put_contents($cacheFile, $content) === false) {
            $this->error('Failed to write helpers cache file.');
            return 1;
        }

        $count = count($helpers);
        $this->success("Helpers cache generated successfully. Discovered {$count} helper(s).");
        foreach ($helpers as $alias => $class) {
            $this->line("  - {$alias} => {$class}");
        }

        return 0;
    }
}