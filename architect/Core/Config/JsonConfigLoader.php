<?php

declare(strict_types=1);

namespace Architect\Core\Config;

use RuntimeException;

/**
 * Loads configuration from JSON files.
 */
class JsonConfigLoader implements ConfigInterface
{
    private array $config = [];
    private bool $loaded = false;

    public function __construct(
        private string $configDir,
        private string $environment
    ) {}

    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->config = [];

        // 1. Load common configuration
        $commonConfigFile = $this->configDir . 'config.json';
        if (file_exists($commonConfigFile)) {
            $this->mergeConfigFromFile($commonConfigFile);
        }

        // 2. Load environment-specific configuration
        $envConfigFile = $this->configDir . 'environment/' . $this->environment . '.json';
        if (file_exists($envConfigFile)) {
            $this->mergeConfigFromFile($envConfigFile);
        }

        $this->loaded = true;
    }

    private function mergeConfigFromFile(string $file): void
    {
        $content = file_get_contents($file);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                "Invalid JSON in {$file}: " . json_last_error_msg()
            );
        }

        if (is_array($data)) {
            $this->config = $this->mergeConfig($this->config, $data);
        }
    }

    private function mergeConfig(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (
                isset($base[$key]) 
                && is_array($base[$key]) 
                && is_array($value)
            ) {
                $base[$key] = $this->mergeConfig($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        
        return $base;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->loaded) {
            $this->load();
        }
        
        // Simple key without dot
        if (strpos($key, '.') === false) {
            return $this->config[$key] ?? $default;
        }
        
        // Nested key with dot notation
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    public function all(): array
    {
        if (!$this->loaded) {
            $this->load();
        }
        
        return $this->config;
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }
}