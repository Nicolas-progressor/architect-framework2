<?php

declare(strict_types=1);

namespace Architect\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;
use Architect\Services\Cache\CacheManager;
use Architect\Services\Cache\Config\CacheConfig;
use Architect\Services\Config\ConfigRepository;

/**
 * Flush data cache stores.
 */
class CacheFlushCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'cache:flush';
    protected string $description = 'Flush data cache stores (file, redis, array)';

    public function getOptions(): array
    {
        return [
            ['--store', 'Specific store to flush (default: all)'],
            ['--driver', 'Flush all stores of a specific driver'],
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
            return $this->flushStore($cache, $store);
        }

        if ($driver !== null) {
            return $this->flushDriver($cache, $driver);
        }

        // Flush all stores
        return $this->flushAll($cache);
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
     * Flush a specific store.
     */
    private function flushStore(CacheManager $cache, string $store): int
    {
        try {
            $storeInstance = $cache->store($store);
            if ($storeInstance->clear()) {
                $this->success("Store '{$store}' flushed successfully.");
                return 0;
            } else {
                $this->warning("Store '{$store}' could not be flushed (maybe empty).");
                return 0;
            }
        } catch (\InvalidArgumentException $e) {
            $this->error("Store '{$store}' does not exist.");
            return 1;
        } catch (\Throwable $e) {
            $this->error("Error flushing store '{$store}': " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Flush all stores of a specific driver.
     */
    private function flushDriver(CacheManager $cache, string $driver): int
    {
        $config = $cache->getConfig();
        $stores = $config->getStoreNames();
        $flushed = 0;
        $errors = 0;

        foreach ($stores as $store) {
            if ($config->getDriver($store) === $driver) {
                try {
                    $storeInstance = $cache->store($store);
                    if ($storeInstance->clear()) {
                        $flushed++;
                    }
                } catch (\Throwable) {
                    $errors++;
                }
            }
        }

        if ($flushed > 0) {
            $this->success("Flushed {$flushed} store(s) for driver '{$driver}'.");
        }
        if ($errors > 0) {
            $this->warning("{$errors} store(s) could not be flushed.");
        }
        if ($flushed === 0 && $errors === 0) {
            $this->info("No stores found for driver '{$driver}'.");
        }

        return $errors > 0 ? 1 : 0;
    }

    /**
     * Flush all stores.
     */
    private function flushAll(CacheManager $cache): int
    {
        $config = $cache->getConfig();
        $stores = $config->getStoreNames();
        $flushed = 0;
        $errors = 0;

        foreach ($stores as $store) {
            try {
                $storeInstance = $cache->store($store);
                if ($storeInstance->clear()) {
                    $flushed++;
                }
            } catch (\Throwable) {
                $errors++;
            }
        }

        if ($flushed > 0) {
            $this->success("Flushed {$flushed} store(s).");
        }
        if ($errors > 0) {
            $this->warning("{$errors} store(s) could not be flushed.");
        }
        if ($flushed === 0 && $errors === 0) {
            $this->info('No stores to flush.');
        }

        return $errors > 0 ? 1 : 0;
    }
}