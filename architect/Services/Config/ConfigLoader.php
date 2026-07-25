<?php

declare(strict_types=1);

namespace Architect\Services\Config;

use Architect\Services\Config\Contracts\ConfigInterface;
use Architect\Services\Config\Contracts\ConfigLoaderInterface;
use Architect\Services\Routing\Contracts\FileSystemInterface;

/**
 * Loads configuration from JSON files.
 * 
 * Uses ConfigPathResolver for file discovery and returns immutable ConfigRepository.
 */
final class ConfigLoader implements ConfigLoaderInterface
{
    /**
     * Create configuration loader.
     *
     * @param FileSystemInterface $fs File system abstraction
     * @param ConfigPathResolver $pathResolver Path resolver
     * @param ConfigCacheInterface|null $cache Configuration cache (optional)
     */
    public function __construct(
        private readonly FileSystemInterface $fs,
        private readonly ConfigPathResolver $pathResolver,
        private readonly ?ConfigCacheInterface $cache = null
    ) {}

    /**
     * @inheritdoc
     */
    public function load(string $name, ?string $appPath = null): ConfigInterface
    {
        // Try to get from cache first
        if ($this->cache !== null && $this->cache->has($name, $appPath)) {
            $cached = $this->cache->get($name, $appPath);
            if ($cached !== null) {
                return $cached;
            }
        }

        $path = $this->pathResolver->resolve($name, $appPath);

        if ($path === null) {
            $config = new ConfigRepository([]);
            
            // Cache empty configuration
            if ($this->cache !== null) {
                $this->cache->put($name, $config, $appPath);
            }
            
            return $config;
        }

        $data = $this->fs->json($path);

        if ($data === null) {
            throw new ConfigLoadException(
                "Failed to parse configuration file: {$path}",
                $name,
                $path
            );
        }

        $config = new ConfigRepository($data);
        
        // Store in cache
        if ($this->cache !== null) {
            $this->cache->put($name, $config, $appPath);
        }

        return $config;
    }

    /**
     * Load and merge multiple configuration files.
     * 
     * Later files override earlier ones (array_replace_recursive).
     * 
     * @param array<string> $names Configuration names in merge order
     * @param string|null $appPath Optional application-specific path
     * @return ConfigInterface
     */
    public function loadAndMerge(array $names, ?string $appPath = null): ConfigInterface
    {
        $merged = [];

        foreach ($names as $name) {
            $config = $this->load($name, $appPath);
            $merged = array_replace_recursive($merged, $config->all());
        }

        return new ConfigRepository($merged);
    }

    /**
     * Load configuration with environment-specific override.
     *
     * Loads base config and merges environment-specific config on top.
     *
     * @param string $name Base configuration name
     * @param string $environment Environment name (e.g., 'development', 'production')
     * @param string|null $appPath Optional application-specific path
     * @return ConfigInterface
     */
    public function loadWithEnvironment(
        string $name,
        string $environment,
        ?string $appPath = null
    ): ConfigInterface {
        $baseConfig = $this->load($name, $appPath);
        
        // Try to load environment-specific config
        $envConfigName = "environment/{$environment}";
        $envPath = $this->pathResolver->resolve($envConfigName, $appPath);

        if ($envPath === null) {
            return $baseConfig;
        }

        $envData = $this->fs->json($envPath);
        
        if ($envData === null) {
            return $baseConfig;
        }

        return new ConfigRepository(
            array_replace_recursive($baseConfig->all(), $envData)
        );
    }

    /**
     * Load configuration with application-specific override.
     *
     * Loads global configuration and merges application-specific configuration on top.
     *
     * @param string $name Configuration name
     * @param string|null $appPath Optional application-specific path
     * @return ConfigInterface
     */
    public function loadWithAppOverride(string $name, ?string $appPath = null): ConfigInterface
    {
        $globalConfig = $this->load($name, null);
        
        if ($appPath === null) {
            return $globalConfig;
        }
        
        $appConfig = $this->load($name, $appPath);
        
        return new ConfigRepository(
            array_replace_recursive($globalConfig->all(), $appConfig->all())
        );
    }
}
