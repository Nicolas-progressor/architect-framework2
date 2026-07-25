<?php

declare(strict_types=1);

namespace Architect\Services\Cache;

use Architect\Services\Cache\Config\CacheConfig;
use Architect\Services\Cache\Contracts\CacheInterface;
use Architect\Services\Cache\Drivers\ArrayCacheDriver;
use Architect\Services\Cache\Drivers\FileCacheDriver;
use Architect\Services\Cache\Drivers\RedisCacheDriver;
use Closure;
use InvalidArgumentException;

/**
 * Cache manager responsible for creating and managing cache stores.
 */
class CacheManager
{
    /**
     * Configuration instance.
     */
    private CacheConfig $config;

    /**
     * Resolved store instances.
     *
     * @var array<string, CacheInterface>
     */
    private array $stores = [];

    /**
     * Custom driver creators.
     *
     * @var array<string, Closure>
     */
    private array $customCreators = [];

    /**
     * @param CacheConfig $config Cache configuration
     */
    public function __construct(CacheConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Get a cache store instance by name.
     *
     * @param string|null $name Store name, null for default
     * @return CacheInterface
     * @throws InvalidArgumentException If store cannot be created.
     */
    public function store(?string $name = null): CacheInterface
    {
        $name ??= $this->config->getDefaultStore();

        if (isset($this->stores[$name])) {
            return $this->stores[$name];
        }

        return $this->stores[$name] = $this->resolve($name);
    }

    /**
     * Alias for store().
     */
    public function driver(?string $name = null): CacheInterface
    {
        return $this->store($name);
    }

    /**
     * Get the default cache driver.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->getDefaultStore();
    }

    /**
     * Set the default cache driver.
     */
    public function setDefaultDriver(string $name): void
    {
        // This would require updating the config, which is not mutable.
        // For simplicity, we'll just note that this is not supported.
        throw new \LogicException('Changing default driver at runtime is not supported.');
    }

    /**
     * Resolve the given store.
     *
     * @param string $name Store name
     * @return CacheInterface
     * @throws InvalidArgumentException
     */
    protected function resolve(string $name): CacheInterface
    {
        $config = $this->config->getStoreConfig($name);
        $driver = $config['driver'] ?? 'file';

        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($config);
        }

        $driverMethod = 'create' . ucfirst($driver) . 'Driver';
        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        throw new InvalidArgumentException(
            sprintf('Cache driver "%s" is not supported.', $driver)
        );
    }

    /**
     * Create an array cache driver.
     */
    protected function createArrayDriver(array $config): CacheInterface
    {
        $driver = new ArrayCacheDriver();
        $driver->setPrefix($this->config->getPrefix());
        return $driver;
    }

    /**
     * Create a file cache driver.
     */
    protected function createFileDriver(array $config): CacheInterface
    {
        $path = $config['path'] ?? (defined('STORAGE_PATH')
            ? STORAGE_PATH . 'cache'
            : dirname(__DIR__, 4) . '/storage/cache');

        $directoryPermissions = $config['directory_permissions'] ?? 0o755;
        $filePermissions = $config['file_permissions'] ?? 0o644;

        $driver = new FileCacheDriver($path, $directoryPermissions, $filePermissions);
        $driver->setPrefix($this->config->getPrefix());
        return $driver;
    }

    /**
     * Create a Redis cache driver.
     */
    protected function createRedisDriver(array $config): CacheInterface
    {
        if (!extension_loaded('redis')) {
            throw new InvalidArgumentException(
                'Redis extension is not loaded. Please install phpredis or disable Redis cache.'
            );
        }

        $driver = RedisCacheDriver::fromConfig($config);
        $driver->setPrefix($this->config->getPrefix());
        return $driver;
    }

    /**
     * Call a custom driver creator.
     */
    protected function callCustomCreator(array $config): CacheInterface
    {
        $driver = $config['driver'];
        $creator = $this->customCreators[$driver];

        $instance = $creator($config);
        if (!$instance instanceof CacheInterface) {
            throw new InvalidArgumentException(
                sprintf('Custom driver "%s" must return an instance of %s.', $driver, CacheInterface::class)
            );
        }

        $instance->setPrefix($this->config->getPrefix());
        return $instance;
    }

    /**
     * Register a custom driver creator.
     *
     * @param string   $driver  Driver name
     * @param Closure $creator Function that receives config and returns a CacheInterface
     */
    public function extend(string $driver, Closure $creator): void
    {
        $this->customCreators[$driver] = $creator;
    }

    /**
     * Determine if a store has been resolved.
     */
    public function hasStore(string $name): bool
    {
        return isset($this->stores[$name]) || $this->config->hasStore($name);
    }

    /**
     * Clear a specific store from the local cache.
     */
    public function forgetStore(string $name): void
    {
        unset($this->stores[$name]);
    }

    /**
     * Get the configuration instance.
     */
    public function getConfig(): CacheConfig
    {
        return $this->config;
    }

    /**
     * Clear all resolved store instances.
     */
    public function clearResolved(): void
    {
        $this->stores = [];
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param string $method
     * @param array  $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->store()->$method(...$parameters);
    }
}
