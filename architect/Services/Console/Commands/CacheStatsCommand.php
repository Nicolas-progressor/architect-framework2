<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;
use Architect\Services\Cache\CacheManager;
use Architect\Services\Cache\Config\CacheConfig;
use Architect\Services\Cache\Drivers\ArrayCacheDriver;
use Architect\Services\Cache\Drivers\FileCacheDriver;
use Architect\Services\Config\ConfigRepository;

/**
 * Show cache statistics.
 */
class CacheStatsCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'cache:stats';
    protected string $description = 'Show cache statistics';

    public function getOptions(): array
    {
        return [
            ['--store', 'Specific store to show stats for (default: all)'],
            ['--driver', 'Show stats for all stores of a specific driver'],
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $cache = $this->createCacheManager();
        if ($cache === null) {
            $this->error('Failed to initialize cache manager.');
            return 1;
        }

        $store = $options['store'] ?? null;
        $driver = $options['driver'] ?? null;

        if ($store !== null && $driver !== null) {
            $this->error('Cannot specify both --store and --driver.');
            return 1;
        }

        if ($store !== null) {
            return $this->showStoreStats($cache, $store);
        }

        if ($driver !== null) {
            return $this->showDriverStats($cache, $driver);
        }

        return $this->showAllStats($cache);
    }

    /**
     * Create cache manager instance.
     */
    private function createCacheManager(): ?CacheManager
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 4);
        $configPath = $root . '/app/config/cache.json';

        if (!file_exists($configPath)) {
            $this->error('Cache configuration not found at ' . $configPath);
            return null;
        }

        try {
            $json = file_get_contents($configPath);
            if ($json === false) {
                $this->error('Cannot read cache configuration file.');
                return null;
            }
            $data = json_decode($json, true);
            if ($data === null) {
                $this->error('Invalid JSON in cache configuration.');
                return null;
            }
            $config = new ConfigRepository($data);
            $cacheConfig = new CacheConfig($config);
            return new CacheManager($cacheConfig);
        } catch (\Throwable $e) {
            $this->error('Error loading cache config: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Show statistics for a specific store.
     */
    private function showStoreStats(CacheManager $cache, string $store): int
    {
        try {
            $storeInstance = $cache->store($store);
            $config = $cache->getConfig();
            $storeConfig = $config->getStoreConfig($store);

            $this->line("Store: <info>{$store}</info>");
            $this->line("Driver: <info>{$storeConfig['driver']}</info>");
            $this->line("Prefix: <info>{$config->getPrefix()}</info>");

            // Driver-specific stats
            if ($storeInstance instanceof ArrayCacheDriver) {
                $this->line('Items in memory: <info>' . $storeInstance->count() . '</info>');
            } elseif ($storeInstance instanceof FileCacheDriver) {
                $dir = $storeInstance->getDirectory();
                $fileCount = count(glob($dir . '/*/*.cache')) + count(glob($dir . '/*.cache'));
                $this->line("Cache directory: <info>{$dir}</info>");
                $this->line("Cache files: <info>{$fileCount}</info>");
            }

            $this->line('');
            return 0;
        } catch (\InvalidArgumentException $e) {
            $this->error("Store '{$store}' does not exist.");
            return 1;
        } catch (\Throwable $e) {
            $this->error("Error retrieving stats for store '{$store}': " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show statistics for all stores of a driver.
     */
    private function showDriverStats(CacheManager $cache, string $driver): int
    {
        $config = $cache->getConfig();
        $stores = $config->getStoreNames();
        $found = false;

        foreach ($stores as $store) {
            if ($config->getDriver($store) === $driver) {
                $found = true;
                $this->showStoreStats($cache, $store);
            }
        }

        if (!$found) {
            $this->info("No stores found for driver '{$driver}'.");
        }

        return 0;
    }

    /**
     * Show statistics for all stores.
     */
    private function showAllStats(CacheManager $cache): int
    {
        $config = $cache->getConfig();
        $stores = $config->getStoreNames();

        if (empty($stores)) {
            $this->info('No cache stores configured.');
            return 0;
        }

        $this->line('<comment>Cache Stores:</comment>');
        foreach ($stores as $store) {
            $driver = $config->getDriver($store);
            $this->line("  - <info>{$store}</info> (<comment>{$driver}</comment>)");
        }

        $this->line('');
        $this->line("<comment>Default store:</comment> <info>{$config->getDefaultStore()}</info>");
        $this->line("<comment>Global prefix:</comment> <info>{$config->getPrefix()}</info>");
        $this->line("<comment>Default TTL:</comment> <info>{$config->getDefaultTtl()} seconds</info>");

        return 0;
    }
}
