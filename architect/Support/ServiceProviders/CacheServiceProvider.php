<?php

declare(strict_types=1);

namespace Architect\Support\ServiceProviders;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Services\Cache\CacheManager;
use Architect\Services\Cache\Config\CacheConfig;
use Architect\Services\Config\Contracts\ConfigInterface;
use Architect\Support\AbstractServiceProvider;

/**
 * Cache service provider.
 */
class CacheServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // Register cache configuration
        $this->registerFactory($container, 'cache.config', function ($c) {
            /** @var ConfigInterface $config */
            $config = $c->get('config.cache');
            return new CacheConfig($config);
        });

        // Register cache manager
        $this->registerFactory($container, 'cache', function ($c) {
            $config = $c->get('cache.config');
            return new CacheManager($config);
        });

        // Register default cache store as 'cache.store' (alias to default driver)
        $this->registerFactory($container, 'cache.store', function ($c) {
            $manager = $c->get('cache');
            return $manager->store();
        });

        // Register array store as 'cache.array'
        $this->registerFactory($container, 'cache.array', function ($c) {
            $manager = $c->get('cache');
            return $manager->store('array');
        });

        // Register file store as 'cache.file'
        $this->registerFactory($container, 'cache.file', function ($c) {
            $manager = $c->get('cache');
            return $manager->store('file');
        });

        // Register redis store as 'cache.redis' (if configured)
        $this->registerFactory($container, 'cache.redis', function ($c) {
            $manager = $c->get('cache');
            return $manager->store('redis');
        });

        // Register cache orchestrator
        $this->registerFactory($container, 'cache.orchestrator', function ($c) {
            return new \Architect\Services\Cache\CacheOrchestrator($c);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container): void
    {
        // Ensure cache directory exists for file driver
        $this->ensureCacheDirectory($container);
    }

    /**
     * Ensure the file cache directory exists.
     */
    private function ensureCacheDirectory(ContainerInterface $container): void
    {
        try {
            $config = $container->get('cache.config');
            $fileConfig = $config->getStoreConfig('file');
        } catch (\InvalidArgumentException) {
            // File store not configured, skip
            return;
        }

        $path = $fileConfig['path'] ?? (defined('STORAGE_PATH')
            ? STORAGE_PATH . 'cache'
            : dirname(__DIR__, 4) . '/storage/cache');

        if (!is_dir($path)) {
            @mkdir($path, 0o755, true);
        }
    }
}
