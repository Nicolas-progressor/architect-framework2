<?php

declare(strict_types=1);

namespace Blueprint\Engine;

use Blueprint\Engine\Config\BlueprintConfig;
use Blueprint\Engine\Cache\CacheManager;
use Blueprint\Engine\Exception\BlueprintException;

/**
 * Template Loader
 * 
 * Handles loading templates from filesystem with support for
 * multiple paths, extensions, and caching.
 * 
 * @package Blueprint\Engine
 */
class Loader
{
    protected BlueprintConfig $config;
    protected CacheManager $cache;
    protected array $paths = [];
    protected array $extensions = ['.blu', '.html'];
    protected array $templateCache = [];
    protected array $appContext = [];

    /**
     * Constructor
     * 
     * @param BlueprintConfig|array $config Configuration or paths array (for BC)
     * @param CacheManager|string|null $cache Cache manager or path (for BC)
     * @param bool $cacheEnabled Cache enabled flag (for BC)
     */
    public function __construct(
        BlueprintConfig|array $config = [],
        CacheManager|string|null $cache = null,
        bool $cacheEnabled = false
    ) {
        // Backward compatibility: accept arrays
        if (is_array($config)) {
            $config = new BlueprintConfig([
                'paths' => $config,
                'cache' => is_string($cache) ? $cache : null,
                'cache_enabled' => $cacheEnabled,
            ]);
        }

        $this->config = $config;
        
        // Initialize cache manager
        if ($cache instanceof CacheManager) {
            $this->cache = $cache;
        } else {
            $this->cache = CacheManager::create(
                $config->getCachePath(),
                $config->isCacheEnabled()
            );
        }

        // Initialize paths from config
        $this->paths = $config->getPaths();
        $this->extensions = $config->getExtensions();
    }

    /**
     * Get configuration
     */
    public function getConfig(): BlueprintConfig
    {
        return $this->config;
    }

    /**
     * Get cache manager
     */
    public function getCacheManager(): CacheManager
    {
        return $this->cache;
    }

    /**
     * Add template path
     * 
     * @param string $path Path to add
     * @return self
     */
    public function addPath(string $path): self
    {
        if (!in_array($path, $this->paths, true)) {
            $this->paths[] = rtrim($path, '/\\');
        }
        return $this;
    }

    /**
     * Set template paths
     * 
     * @param array $paths Array of paths
     * @return self
     */
    public function setPaths(array $paths): self
    {
        $this->paths = [];
        foreach ($paths as $path) {
            $this->addPath($path);
        }
        return $this;
    }

    /**
     * Get template paths
     * 
     * @return array
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * Add template extension
     * 
     * @param string $extension Extension to add
     * @return self
     */
    public function addExtension(string $extension): self
    {
        if (!in_array($extension, $this->extensions, true)) {
            $this->extensions[] = $extension;
        }
        return $this;
    }

    /**
     * Set template extensions
     * 
     * @param array $extensions Array of extensions
     * @return self
     */
    public function setExtensions(array $extensions): self
    {
        $this->extensions = $extensions;
        return $this;
    }

    /**
     * Get template extensions
     * 
     * @return array
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * Set cache path
     * 
     * @param string $path Cache directory path
     * @return self
     */
    public function setCachePath(string $path): self
    {
        $this->cache->setCachePath($path);
        return $this;
    }

    /**
     * Get cache path
     * 
     * @return string
     */
    public function getCachePath(): string
    {
        return $this->cache->getCachePath();
    }

    /**
     * Set cache enabled
     * 
     * @param bool $enabled Cache enabled flag
     * @return self
     */
    public function setCacheEnabled(bool $enabled): self
    {
        $this->cache->setEnabled($enabled);
        return $this;
    }

    /**
     * Check if cache is enabled
     * 
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->cache->isEnabled();
    }

    /**
     * Find template file
     * 
     * @param string $name Template name
     * @return string|null
     */
    public function findTemplate(string $name): ?string
    {
        // Check memory cache
        $cacheKey = $this->getTemplateCacheKey($name);
        if (isset($this->templateCache[$cacheKey])) {
            return $this->templateCache[$cacheKey];
        }

        // Try different extensions
        foreach ($this->extensions as $extension) {
            // Absolute path
            if ($this->isAbsolutePath($name)) {
                if (file_exists($name . $extension)) {
                    $this->templateCache[$cacheKey] = $name . $extension;
                    return $this->templateCache[$cacheKey];
                }
                if (file_exists($name)) {
                    $this->templateCache[$cacheKey] = $name;
                    return $this->templateCache[$cacheKey];
                }
                continue;
            }

            // Search in paths
            foreach ($this->paths as $path) {
                $fullPath = $path . '/' . $name . $extension;
                if (file_exists($fullPath)) {
                    $this->templateCache[$cacheKey] = $fullPath;
                    return $fullPath;
                }

                // Without extension
                $fullPathNoExt = $path . '/' . $name;
                if (file_exists($fullPathNoExt)) {
                    $this->templateCache[$cacheKey] = $fullPathNoExt;
                    return $fullPathNoExt;
                }
            }
        }

        return null;
    }

    /**
     * Check if template exists
     * 
     * @param string $name Template name
     * @return bool
     */
    public function exists(string $name): bool
    {
        return $this->findTemplate($name) !== null;
    }

    /**
     * Get template source content
     * 
     * @param string $name Template name
     * @return string
     * @throws BlueprintException
     */
    public function getSource(string $name): string
    {
        $path = $this->findTemplate($name);
        
        if ($path === null) {
            throw BlueprintException::loaderError(
                "Template \"{$name}\" not found",
                $name
            );
        }

        $content = file_get_contents($path);
        
        if ($content === false) {
            throw BlueprintException::loaderError(
                "Failed to read template \"{$name}\"",
                $name
            );
        }

        return $content;
    }

    /**
     * Get template path
     * 
     * @param string $name Template name
     * @return string|null
     */
    public function getPath(string $name): ?string
    {
        return $this->findTemplate($name);
    }

    /**
     * Get relative template path
     * 
     * @param string $name Template name
     * @return string
     */
    public function getRelativePath(string $name): string
    {
        $path = $this->findTemplate($name);
        
        if ($path === null) {
            return $name;
        }

        // Remove common paths
        foreach ($this->paths as $basePath) {
            if (str_starts_with($path, $basePath)) {
                return ltrim(str_replace($basePath, '', $path), '/');
            }
        }

        return $path;
    }

    /**
     * Get cache key for template
     * 
     * @param string $name Template name
     * @return string
     */
    public function getCacheKey(string $name): string
    {
        return md5($name . ':' . implode(':', $this->paths));
    }

    /**
     * Get compiled template path
     * 
     * @param string $name Template name
     * @return string
     */
    public function getCompiledPath(string $name): string
    {
        $cacheKey = $this->getCacheKey($name);
        return $this->cache->getCachePath() . '/' . $cacheKey . '.php';
    }

    /**
     * Check if compilation is needed (template is fresh)
     * 
     * @param string $name Template name
     * @return bool
     */
    public function isFresh(string $name): bool
    {
        if (!$this->cache->isEnabled()) {
            return false;
        }

        $sourcePath = $this->findTemplate($name);
        if ($sourcePath === null) {
            return false;
        }

        $compiledPath = $this->getCompiledPath($name);
        
        if (!file_exists($compiledPath)) {
            return false;
        }

        return filemtime($compiledPath) >= filemtime($sourcePath);
    }

    /**
     * Set application context
     * 
     * @param array $context Context array
     * @return self
     */
    public function setAppContext(array $context): self
    {
        $this->appContext = $context;
        return $this;
    }

    /**
     * Get application context
     * 
     * @return array
     */
    public function getAppContext(): array
    {
        return $this->appContext;
    }

    /**
     * Add path for specific application
     * 
     * @param string $app App name
     * @param string $templateDir Template directory pattern
     * @return self
     */
    public function addAppPath(string $app, string $templateDir): self
    {
        $path = str_replace('{app}', $app, $templateDir);
        return $this->addPath($path);
    }

    /**
     * Clear all template cache
     * 
     * @return bool
     */
    public function clearCache(): bool
    {
        $this->templateCache = [];
        return $this->cache->clear();
    }

    /**
     * Clear cache for specific template
     * 
     * @param string $name Template name
     * @return bool
     */
    public function clearCacheFor(string $name): bool
    {
        $cacheKey = $this->getTemplateCacheKey($name);
        unset($this->templateCache[$cacheKey]);
        return $this->cache->clearFor($name, $this->paths);
    }

    /**
     * Get template cache key for memory cache
     */
    protected function getTemplateCacheKey(string $name): string
    {
        return md5($name . ':' . implode(':', $this->paths));
    }

    /**
     * Get list of all available templates
     * 
     * @return array
     */
    public function getTemplateList(): array
    {
        $templates = [];

        foreach ($this->paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = ltrim(str_replace($path, '', $file->getPathname()), '/');
                    
                    // Remove extension
                    foreach ($this->extensions as $ext) {
                        if (str_ends_with($relativePath, $ext)) {
                            $relativePath = substr($relativePath, 0, -strlen($ext));
                            break;
                        }
                    }

                    $templates[] = $relativePath;
                }
            }
        }

        return array_unique($templates);
    }

    /**
     * Check if path is absolute
     * 
     * @param string $path Path to check
     * @return bool
     */
    protected function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:/', $path);
    }
}
