<?php

declare(strict_types=1);

namespace Architect\Services\Routing;

use Architect\Services\Routing\Contracts\RouteCacheInterface;
use Architect\Services\Routing\Contracts\FileSystemInterface;

class RouteCache implements RouteCacheInterface
{
    private const CACHE_DIR = 'bootstrap/cache/routes';
    private const CACHE_FILE = 'routes.php';
    
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
     * Check if routes are cached for an application.
     */
    public function has(string $appDir): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        $this->loadCache();
        $key = $this->getCacheKey($appDir);
        
        return isset($this->cache[$key]);
    }
    
    /**
     * Get cached routes for an application.
     */
    public function get(string $appDir): ?array
    {
        if (!$this->enabled) {
            return null;
        }
        
        $this->loadCache();
        $key = $this->getCacheKey($appDir);
        
        return $this->cache[$key] ?? null;
    }
    
    /**
     * Store routes in cache.
     */
    public function put(string $appDir, array $routes): void
    {
        if (!$this->enabled) {
            return;
        }
        
        $this->loadCache();
        $key = $this->getCacheKey($appDir);
        
        $this->cache[$key] = $routes;
        $this->saveCache();
    }
    
    /**
     * Clear route cache.
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
     * Clear cache for specific application.
     */
    public function clearFor(string $appDir): void
    {
        $this->loadCache();
        $key = $this->getCacheKey($appDir);
        
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
            $this->saveCache();
        }
    }
    
    /**
     * Get cache key for application routes.
     */
    public function getCacheKey(string $appDir): string
    {
        // Include modification time of route files for cache invalidation
        $mtime = $this->getRouteFilesMtime($appDir);
        return md5($appDir . ':' . $mtime);
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
            $this->fs->mkdir($cacheDir, 0755, true);
        }
    }
    
    /**
     * Get modification time of route files for cache invalidation.
     */
    private function getRouteFilesMtime(string $appDir): int
    {
        $routesDir = $appDir . '/routes';
        
        if (!$this->fs->exists($routesDir)) {
            return 0;
        }
        
        $files = $this->fs->glob($routesDir . '/*.json');
        
        if (empty($files)) {
            return 0;
        }
        
        $maxMtime = 0;
        foreach ($files as $file) {
            $mtime = $this->fs->mtime($file);
            if ($mtime > $maxMtime) {
                $maxMtime = $mtime;
            }
        }
        
        return $maxMtime;
    }
}