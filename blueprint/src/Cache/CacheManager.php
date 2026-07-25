<?php

declare(strict_types=1);

namespace Blueprint\Engine\Cache;

use Blueprint\Engine\Config\BlueprintConfig;

/**
 * Cache Manager
 * 
 * Handles caching of compiled templates with automatic invalidation.
 * 
 * @package Blueprint\Engine\Cache
 */
class CacheManager
{
    protected string $cachePath;
    protected bool $enabled;
    protected array $compiledFiles = [];

    /**
     * Constructor
     * 
     * @param BlueprintConfig $config Configuration
     */
    public function __construct(BlueprintConfig $config)
    {
        $this->cachePath = $config->getCachePath();
        $this->enabled = $config->isCacheEnabled();
    }

    /**
     * Create from path and enabled flag
     * 
     * @param string $cachePath Cache directory path
     * @param bool $enabled Is cache enabled
     * @return self
     */
    public static function create(string $cachePath, bool $enabled = false): self
    {
        $config = new BlueprintConfig([
            'cache' => $cachePath,
            'cache_enabled' => $enabled,
        ]);
        
        return new self($config);
    }

    /**
     * Check if cache is enabled
     * 
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Enable or disable cache
     * 
     * @param bool $enabled Enable flag
     * @return self
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Get cache directory path
     * 
     * @return string
     */
    public function getCachePath(): string
    {
        return $this->cachePath;
    }

    /**
     * Set cache directory path
     * 
     * @param string $path Cache directory path
     * @return self
     */
    public function setCachePath(string $path): self
    {
        $this->cachePath = rtrim($path, '/\\');
        return $this;
    }

    /**
     * Get cache key for template
     * 
     * @param string $templateName Template name
     * @param array $paths Template search paths (for uniqueness)
     * @return string
     */
    public function getCacheKey(string $templateName, array $paths = []): string
    {
        return md5($templateName . ':' . implode(':', $paths));
    }

    /**
     * Get compiled template file path
     * 
     * @param string $templateName Template name
     * @param array $paths Template search paths
     * @return string
     */
    public function getCompiledPath(string $templateName, array $paths = []): string
    {
        $key = $this->getCacheKey($templateName, $paths);
        return $this->cachePath . '/' . $key . '.php';
    }

    /**
     * Check if cached version exists and is fresh
     * 
     * @param string $templateName Template name
     * @param string $sourcePath Source file path
     * @param array $paths Template search paths
     * @return bool
     */
    public function isFresh(string $templateName, string $sourcePath, array $paths = []): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $compiledPath = $this->getCompiledPath($templateName, $paths);

        if (!file_exists($compiledPath)) {
            return false;
        }

        if (!file_exists($sourcePath)) {
            return false;
        }

        return filemtime($compiledPath) >= filemtime($sourcePath);
    }

    /**
     * Check if cached version exists
     * 
     * @param string $templateName Template name
     * @param array $paths Template search paths
     * @return bool
     */
    public function exists(string $templateName, array $paths = []): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $compiledPath = $this->getCompiledPath($templateName, $paths);
        return file_exists($compiledPath);
    }

    /**
     * Load compiled template from cache
     * 
     * @param string $templateName Template name
     * @param array $paths Template search paths
     * @return string|null Compiled PHP code or null if not cached
     */
    public function load(string $templateName, array $paths = []): ?string
    {
        if (!$this->enabled) {
            return null;
        }

        $compiledPath = $this->getCompiledPath($templateName, $paths);

        if (!file_exists($compiledPath)) {
            return null;
        }

        $content = file_get_contents($compiledPath);
        return $content !== false ? $content : null;
    }

    /**
     * Store compiled template in cache
     * 
     * @param string $templateName Template name
     * @param string $compiledCode Compiled PHP code
     * @param array $paths Template search paths
     * @return bool Success
     */
    public function store(string $templateName, string $compiledCode, array $paths = []): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $compiledPath = $this->getCompiledPath($templateName, $paths);
        $dir = dirname($compiledPath);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                return false;
            }
        }

        $result = file_put_contents($compiledPath, $compiledCode) !== false;
        
        if ($result) {
            $this->compiledFiles[$templateName] = $compiledPath;
        }

        return $result;
    }

    /**
     * Include and execute cached template
     * 
     * @param string $templateName Template name
     * @param array $paths Template search paths
     * @param array $context Template context
     * @param object $blueprint Blueprint instance
     * @return string|null Rendered output or null if not cached
     */
    public function execute(string $templateName, array $paths, array $context, object $blueprint): ?string
    {
        if (!$this->enabled) {
            return null;
        }

        $compiledPath = $this->getCompiledPath($templateName, $paths);

        if (!file_exists($compiledPath)) {
            return null;
        }

        ob_start();

        try {
            $__context = $context;
            $__template = $blueprint;
            
            include $compiledPath;
            
            return ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Clear all cache
     * 
     * @return bool Success
     */
    public function clear(): bool
    {
        if (!is_dir($this->cachePath)) {
            return true;
        }

        $files = glob($this->cachePath . '/*.php');
        $result = true;

        foreach ($files as $file) {
            if (!@unlink($file)) {
                $result = false;
            }
        }

        $this->compiledFiles = [];
        return $result;
    }

    /**
     * Clear cache for specific template
     * 
     * @param string $templateName Template name
     * @param array $paths Template search paths
     * @return bool Success
     */
    public function clearFor(string $templateName, array $paths = []): bool
    {
        $compiledPath = $this->getCompiledPath($templateName, $paths);

        unset($this->compiledFiles[$templateName]);

        if (file_exists($compiledPath)) {
            return @unlink($compiledPath);
        }

        return true;
    }

    /**
     * Get list of cached files
     * 
     * @return array Array of file info
     */
    public function getCachedFiles(): array
    {
        $files = [];

        if (!is_dir($this->cachePath)) {
            return $files;
        }

        $cachedFiles = glob($this->cachePath . '/*.php');

        foreach ($cachedFiles as $file) {
            $files[] = [
                'path' => $file,
                'name' => basename($file),
                'size' => filesize($file),
                'modified' => filemtime($file),
            ];
        }

        return $files;
    }

    /**
     * Get cache statistics
     * 
     * @return array Statistics
     */
    public function getStats(): array
    {
        $files = $this->getCachedFiles();
        $totalSize = array_sum(array_column($files, 'size'));

        return [
            'enabled' => $this->enabled,
            'path' => $this->cachePath,
            'files_count' => count($files),
            'total_size' => $totalSize,
            'files' => $files,
        ];
    }
}
