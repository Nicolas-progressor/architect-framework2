<?php

declare(strict_types=1);

namespace Architect\Helpers\Core;

use Architect\Helpers\Core\Contracts\HelperInterface;
use RuntimeException;

/**
 * Discovers helper classes implementing HelperInterface.
 * Uses naming convention and configuration, no file scanning.
 */
class HelperDiscovery
{
    /**
     * @var array<string, string> Cache of discovered helpers [alias => class]
     */
    private array $discoveredHelpers = [];

    /**
     * @var bool Whether discovery has been performed
     */
    private bool $discovered = false;

    /**
     * @var string|null Path to cache file
     */
    private ?string $cachePath = null;

    /**
     * @var bool Whether to use cache (auto-determined based on APP_DEBUG)
     */
    private bool $useCache = true;

    /**
     * Default mapping of alias to class (based on convention)
     * @var array<string, string>
     */
    private const DEFAULT_MAPPING = [
        'title' => 'Architect\Helpers\Title\TitleHelper',
        'breadcrumbs' => 'Architect\Helpers\Breadcrumbs\BreadcrumbsHelper',
        'html' => 'Architect\Helpers\Html\HtmlHelper',
        'assets' => 'Architect\Helpers\Assets\AssetsHelper',
        'request' => 'Architect\Helpers\Request\RequestHelper',
        'db' => 'Architect\Helpers\Db\DbHelper',
        'arr' => 'Architect\Helpers\ArrayHelper\ArrayHelper',
        'number' => 'Architect\Helpers\NumberHelper\NumberHelper',
    ];

    public function __construct()
    {
        // No scanning paths needed
    }

    /**
     * Get the cache file path.
     */
    public function getCachePath(): string
    {
        if ($this->cachePath === null) {
            $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 3);
            $this->cachePath = $root . '/bootstrap/cache/helpers.php';
        }
        return $this->cachePath;
    }

    /**
     * Set a custom cache file path.
     */
    public function setCachePath(string $path): void
    {
        $this->cachePath = $path;
    }

    /**
     * Determine whether cache should be used.
     */
    public function shouldUseCache(): bool
    {
        return $this->useCache;
    }

    /**
     * Enable or disable cache usage.
     */
    public function setUseCache(bool $useCache): void
    {
        $this->useCache = $useCache;
    }

    /**
     * Load discovered helpers from cache file.
     */
    private function loadFromCache(): bool
    {
        $cacheFile = $this->getCachePath();
        if (!file_exists($cacheFile)) {
            return false;
        }
        // Invalidate cache if older than 24 hours (86400 seconds)
        if (filemtime($cacheFile) < time() - 86400) {
            return false;
        }
        $helpers = include $cacheFile;
        if (!is_array($helpers)) {
            return false;
        }
        $this->discoveredHelpers = $helpers;
        $this->discovered = true;
        return true;
    }

    /**
     * Store discovered helpers to cache file.
     */
    private function storeToCache(): bool
    {
        $cacheFile = $this->getCachePath();
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $content = '<?php' . PHP_EOL . PHP_EOL . 'return ' . var_export($this->discoveredHelpers, true) . ';';
        return file_put_contents($cacheFile, $content) !== false;
    }

    /**
     * Discover all helper classes.
     *
     * @return array<string, string> Map of alias to class name
     */
    public function discover(): array
    {
        if ($this->discovered) {
            return $this->discoveredHelpers;
        }

        // Try to load from cache if enabled
        if ($this->shouldUseCache() && $this->loadFromCache()) {
            return $this->discoveredHelpers;
        }

        $this->discoveredHelpers = self::DEFAULT_MAPPING;

        // Additionally, try to discover via configuration if available
        // (This could be extended to read from config.helpers, but we skip for simplicity)

        $this->discovered = true;

        // Store to cache if enabled
        if ($this->shouldUseCache()) {
            $this->storeToCache();
        }

        return $this->discoveredHelpers;
    }

    /**
     * Get discovered helper classes as an array of class names.
     *
     * @return array<string>
     */
    public function getDiscoveredClasses(): array
    {
        $this->discover();
        return array_values($this->discoveredHelpers);
    }

    /**
     * Get discovered helper aliases.
     *
     * @return array<string>
     */
    public function getDiscoveredAliases(): array
    {
        $this->discover();
        return array_keys($this->discoveredHelpers);
    }

    /**
     * Add a directory to scan for helper classes (deprecated, does nothing).
     * @deprecated
     */
    public function addDirectory(string $directory): void
    {
        // No-op
    }

    /**
     * Add a namespace prefix to scan for helper classes (deprecated, does nothing).
     * @deprecated
     */
    public function addNamespace(string $namespace): void
    {
        // No-op
    }
}