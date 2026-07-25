<?php

declare(strict_types=1);

namespace Architect\Services\Blueprint\Config;

use Architect\Services\Blueprint\Contracts\BlueprintConfigInterface;

/**
 * Blueprint configuration with cascading support
 */
final class BlueprintConfig implements BlueprintConfigInterface
{
    private array $config;
    private string $rootDir;

    public function __construct(array $config = [], ?string $rootDir = null)
    {
        $this->rootDir = $rootDir ?? $this->detectRootDir();
        $this->config = $this->mergeWithDefaults($config);
    }

    /**
     * Load configuration from file paths with cascade
     */
    public static function fromPaths(array $paths, string $rootDir): self
    {
        $config = [];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $decoded = json_decode($content, true) ?? [];
                $config = array_merge($config, $decoded);
            }
        }

        return new self($config, $rootDir);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->config;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->config);
    }

    public function getPaths(): array
    {
        return $this->config['paths'] ?? [];
    }

    public function getCachePath(): ?string
    {
        $path = $this->config['cache'] ?? null;

        if ($path && !$this->isAbsolutePath($path)) {
            $path = $this->rootDir . '/' . ltrim($path, '/');
        }

        return $path;
    }

    public function isCacheEnabled(): bool
    {
        return (bool) ($this->config['cache_enabled'] ?? false);
    }

    public function isDebug(): bool
    {
        return (bool) ($this->config['debug'] ?? false);
    }

    public function getExtensions(): array
    {
        return $this->config['extensions'] ?? ['.blu', '.html'];
    }

    public function getElementsDirs(): array
    {
        $dirs = $this->config['elements_dirs'] ?? [];

        return array_map(function (string $path): string {
            if (!$this->isAbsolutePath($path)) {
                return $this->rootDir . '/' . ltrim($path, '/');
            }
            return $path;
        }, $dirs);
    }

    public function isAutoescape(): bool
    {
        return (bool) ($this->config['autoescape'] ?? true);
    }

    public function isStrictVariables(): bool
    {
        return (bool) ($this->config['strict_variables'] ?? false);
    }

    public function showErrors(): bool
    {
        return (bool) ($this->config['show_errors'] ?? true);
    }

    /**
     * Merge user config with defaults
     */
    private function mergeWithDefaults(array $config): array
    {
        $defaults = [
            'debug' => defined('APP_DEBUG') ? APP_DEBUG : false,
            'autoescape' => true,
            'cache' => $this->rootDir . '/cache/blueprints/',
            'cache_enabled' => false,
            'paths' => [$this->rootDir . '/app/template/'],
            'extensions' => ['.blu', '.html'],
            'strict_variables' => false,
            'show_errors' => true,
        ];

        return array_merge($defaults, $config);
    }

    /**
     * Detect root directory
     */
    private function detectRootDir(): string
    {
        if (defined('ROOT_DIR')) {
            return ROOT_DIR;
        }

        return dirname(__DIR__, 4);
    }

    /**
     * Check if path is absolute
     */
    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:/', $path);
    }
}
