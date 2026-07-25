<?php

declare(strict_types=1);

namespace Blueprint\Engine\Config;

/**
 * Immutable Blueprint Configuration
 * 
 * Handles configuration with path resolution, environment detection,
 * and sensible defaults.
 * 
 * @package Blueprint\Engine\Config
 */
class BlueprintConfig
{
    protected array $config;
    protected string $rootDir;

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->rootDir = $this->detectRootDir();
        $this->config = $this->mergeWithDefaults($config);
    }

    /**
     * Detect project root directory
     */
    protected function detectRootDir(): string
    {
        if (defined('ROOT_DIR')) {
            return ROOT_DIR;
        }
        
        return dirname(__DIR__, 4);
    }

    /**
     * Merge user config with defaults
     */
    protected function mergeWithDefaults(array $config): array
    {
        $defaults = [
            'debug' => false,
            'autoescape' => true,
            'cache' => $this->rootDir . '/cache/blueprints/',
            'cache_enabled' => false,
            'paths' => [
                $this->rootDir . '/app/template/',
                $this->rootDir . '/app/home/template/',
            ],
            'extensions' => ['.blu', '.twig', '.html'],
            'strict_variables' => false,
            'show_errors' => true,
            'elements_dirs' => [],
        ];

        return array_merge($defaults, $config);
    }

    /**
     * Get configuration value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set configuration value
     */
    public function set(string $key, mixed $value): self
    {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->config);
    }

    /**
     * Get all configuration as array
     */
    public function toArray(): array
    {
        return $this->config;
    }

    /**
     * Get template paths
     */
    public function getPaths(): array
    {
        $paths = $this->config['paths'] ?? [];
        
        return array_map(function ($path) {
            if (!$this->isAbsolutePath($path)) {
                return $this->rootDir . '/' . ltrim($path, '/');
            }
            return $path;
        }, $paths);
    }

    /**
     * Get file extensions
     */
    public function getExtensions(): array
    {
        return $this->config['extensions'] ?? ['.blu', '.html'];
    }

    /**
     * Get cache path
     */
    public function getCachePath(): string
    {
        $path = $this->config['cache'] ?? $this->rootDir . '/cache/blueprints/';
        
        if (!$this->isAbsolutePath($path)) {
            return $this->rootDir . '/' . ltrim($path, '/');
        }
        
        return $path;
    }

    /**
     * Check if cache is enabled
     */
    public function isCacheEnabled(): bool
    {
        return (bool) ($this->config['cache_enabled'] ?? false);
    }

    /**
     * Check if debug mode is enabled
     */
    public function isDebug(): bool
    {
        return (bool) ($this->config['debug'] ?? false);
    }

    /**
     * Check if autoescape is enabled
     */
    public function isAutoescape(): bool
    {
        return (bool) ($this->config['autoescape'] ?? true);
    }

    /**
     * Check if strict variables mode is enabled
     */
    public function isStrictVariables(): bool
    {
        return (bool) ($this->config['strict_variables'] ?? false);
    }

    /**
     * Check if errors should be shown
     */
    public function showErrors(): bool
    {
        return (bool) ($this->config['show_errors'] ?? true);
    }

    /**
     * Get elements directories
     */
    public function getElementsDirs(): array
    {
        return $this->config['elements_dirs'] ?? [];
    }

    /**
     * Get root directory
     */
    public function getRootDir(): string
    {
        return $this->rootDir;
    }

    /**
     * Check if path is absolute
     */
    protected function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:/', $path);
    }

    /**
     * Create from JSON file
     */
    public static function fromFile(string $path): self
    {
        if (!file_exists($path)) {
            return new self([]);
        }

        $content = file_get_contents($path);
        $config = json_decode($content, true) ?? [];
        
        return new self($config);
    }

    /**
     * Create with environment detection
     */
    public static function createForEnvironment(?string $environment = null): self
    {
        $rootDir = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 4);
        
        $configPath = $rootDir . '/app/config/blueprint.json';
        $config = [];
        
        if (file_exists($configPath)) {
            $content = file_get_contents($configPath);
            $config = json_decode($content, true) ?? [];
        }
        
        if ($environment === null) {
            $environment = getenv('APP_ENV') ?: 'production';
        }
        
        $envConfigPath = $rootDir . '/app/config/environment/blueprint/' . $environment . '.json';
        
        if (file_exists($envConfigPath)) {
            $envContent = file_get_contents($envConfigPath);
            $envConfig = json_decode($envContent, true) ?? [];
            $config = array_merge($config, $envConfig);
        }
        
        return new self($config);
    }
}
