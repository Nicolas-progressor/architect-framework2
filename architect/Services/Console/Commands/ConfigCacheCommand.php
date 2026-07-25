<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;

/**
 * Cache configuration files
 */
class ConfigCacheCommand extends BaseCommand implements CommandInterface
{
    protected string$name = 'config:cache';
    protected string $description = 'Cache configuration files for better performance';

    public function execute(array $arguments, array $options): int
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 4);
        $appDir = defined('APP_DIR') ? APP_DIR : $root . '/app';
        $cacheDir = $root . '/bootstrap/cache';

        // Create cache directory
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cached = [];
        $errors = [];

        // Cache apps.json
        $result = $this->cacheConfigFile($appDir . '/config/apps.json', $cacheDir . '/apps.php');
        if ($result === true) {
            $cached[] = 'apps.json';
        } elseif ($result === false) {
            $errors[] = 'apps.json';
        }

        // Cache router.json
        $result = $this->cacheConfigFile($appDir . '/config/router.json', $cacheDir . '/router.php');
        if ($result === true) {
            $cached[] = 'router.json';
        } elseif ($result === false) {
            $errors[] = 'router.json';
        }

        // Cache config.json
        $result = $this->cacheConfigFile($appDir . '/config/config.json', $cacheDir . '/config.php');
        if ($result === true) {
            $cached[] = 'config.json';
        } elseif ($result === false) {
            $errors[] = 'config.json';
        }

        // Cache debug.json
        $result = $this->cacheConfigFile($appDir . '/config/debug.json', $cacheDir . '/debug.php');
        if ($result === true) {
            $cached[] = 'debug.json';
        } elseif ($result === false) {
            $errors[] = 'debug.json';
        }

        // Cache routes
        $routesCached = $this->cacheRoutes($appDir, $cacheDir);
        if ($routesCached) {
            $cached[] = 'routes';
        }

        if (!empty($cached)) {
            $this->success('Configuration cached successfully:');
            foreach ($cached as $item) {
                $this->line("  - {$item}");
            }
        }

        if (!empty($errors)) {
            $this->warning('Failed to cache some files:');
            foreach ($errors as $item) {
                $this->line("  - {$item}");
            }
        }

        return empty($errors) ? 0 : 1;
    }

    /**
     * Cache a single config file
     */
    protected function cacheConfigFile(string $source, string $dest): ?bool
    {
        if (!file_exists($source)) {
            return null;
        }

        $data = json_decode(file_get_contents($source), true);

        if ($data === null) {
            return false;
        }

        $content = '<?php' . PHP_EOL . PHP_EOL;
        $content .= 'return ' . var_export($data, true) . ';';

        return file_put_contents($dest, $content) !== false;
    }

    /**
     * Cache all routes
     */
    protected function cacheRoutes(string $appDir, string $cacheDir): bool
    {
        $routes = [];

        // Global routes
        $globalRoutesPath = $appDir . '/routes/routes.json';
        if (file_exists($globalRoutesPath)) {
            $routes['global'] = json_decode(file_get_contents($globalRoutesPath), true);
        }

        // Load apps
        $configPath = $appDir . '/config/apps.json';
        $apps = ['home'];
        if (file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath), true);
            if ($config && isset($config['apps'])) {
                $apps = array_merge($apps, $config['apps']);
            }
        }

        // App routes
        foreach ($apps as $app) {
            $appRoutesPath = "{$appDir}/{$app}/routes/routes.json";
            if (file_exists($appRoutesPath)) {
                $routes[$app] = json_decode(file_get_contents($appRoutesPath), true);
            }
        }

        $content = '<?php' . PHP_EOL . PHP_EOL;
        $content .= 'return ' . var_export($routes, true) . ';';

        return file_put_contents("{$cacheDir}/routes.php", $content) !== false;
    }
}
