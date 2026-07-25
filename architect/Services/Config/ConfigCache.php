<?php

declare(strict_types=1);

namespace Architect\Services\Config;

use Architect\Services\Config\Contracts\ConfigCacheInterface;
use Architect\Services\Config\Contracts\ConfigInterface;
use Architect\Services\Routing\Contracts\FileSystemInterface;

class ConfigCache implements ConfigCacheInterface
{
    private const CACHE_DIR = 'bootstrap/cache/config';
    private const CACHE_FILE = 'config.php';

    private bool $enabled = true;
    private array $cache = [];
    private bool $loaded = false;

    public function __construct(
        private readonly FileSystemInterface $fs,
        private readonly string $cachePath
    ) {
        $this->ensureCacheDirectory();
    }

    /**
     * Check if configuration is cached.
     */
    public function has(string $name, ?string $appPath = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $this->loadCache();
        $key = $this->getCacheKey($name, $appPath);

        return isset($this->cache[$key]);
    }

    /**
     * Get cached configuration.
     */
    public function get(string $name, ?string $appPath = null): ?ConfigInterface
    {
        if (!$this->enabled) {
            return null;
        }

        $this->loadCache();
        $key = $this->getCacheKey($name, $appPath);

        if (!isset($this->cache[$key])) {
            return null;
        }

        return new ConfigRepository($this->cache[$key]);
    }

    /**
     * Store configuration in cache.
     */
    public function put(string $name, ConfigInterface $config, ?string $appPath = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->loadCache();
        $key = $this->getCacheKey($name, $appPath);

        $this->cache[$key] = $config->all();
        $this->saveCache();
    }

    /**
     * Clear configuration cache.
     */
    public function clear(): void
    {
        $this->cache = [];
        $this->loaded = false;

        if ($this->fs->exists($this->getCacheFilePath())) {
            $this->fs->unlink($this->getCacheFilePath());
        }
    }

    /**
     * Clear cache for specific configuration.
     */
    public function clearFor(string $name, ?string $appPath = null): void
    {
        $this->loadCache();
        $key = $this->getCacheKey($name, $appPath);

        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
            $this->saveCache();
        }
    }

    /**
     * Get cache key for configuration.
     */
    public function getCacheKey(string $name, ?string $appPath = null): string
    {
        $parts = [$name];

        if ($appPath !== null) {
            $parts[] = $appPath;
        }

        return md5(implode(':', $parts));
    }

    /**
     * Check if cache is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Enable or disable cache.
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Load cache from file.
     */
    private function loadCache(): void
    {
        if ($this->loaded || !$this->enabled) {
            return;
        }

        $cacheFile = $this->getCacheFilePath();

        if ($this->fs->exists($cacheFile)) {
            $data = include $cacheFile;

            if (is_array($data)) {
                $this->cache = $data;
            }
        }

        $this->loaded = true;
    }

    /**
     * Save cache to file.
     */
    private function saveCache(): void
    {
        if (!$this->enabled) {
            return;
        }

        $cacheFile = $this->getCacheFilePath();
        $content = '<?php' . PHP_EOL . 'return ' . var_export($this->cache, true) . ';' . PHP_EOL;

        $this->fs->put($cacheFile, $content);
    }

    /**
     * Get full path to cache file.
     */
    private function getCacheFilePath(): string
    {
        return $this->cachePath . '/' . self::CACHE_DIR . '/' . self::CACHE_FILE;
    }

    /**
     * Ensure cache directory exists.
     */
    private function ensureCacheDirectory(): void
    {
        $cacheDir = $this->cachePath . '/' . self::CACHE_DIR;

        if (!$this->fs->exists($cacheDir)) {
            $this->fs->mkdir($cacheDir, 0o755, true);
        }
    }
}
