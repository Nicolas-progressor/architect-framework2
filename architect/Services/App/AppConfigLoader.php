<?php

declare(strict_types=1);

namespace Architect\Services\App;

use Psr\Log\LoggerInterface;

/**
 * Loader for application configuration files.
 * 
 * Handles loading and validation of app.json configuration files.
 */
class AppConfigLoader
{
    /**
     * Configuration file names to search (in order).
     */
    private const CONFIG_FILES = [
        'config/app.json',
        'app.json',
    ];

    /**
     * Create config loader.
     */
    public function __construct(
        private readonly ?LoggerInterface $logger = null
    ) {}

    /**
     * Load application configuration from directory.
     * 
     * @return array<string, mixed>
     */
    public function load(string $appDir): array
    {
        foreach (self::CONFIG_FILES as $configFile) {
            $path = $appDir . $configFile;
            
            if (!file_exists($path)) {
                continue;
            }

            $config = $this->parseJsonFile($path);
            
            if ($config !== null) {
                return $config;
            }
        }

        return [];
    }

    /**
     * Parse JSON file with validation.
     * 
     * @return array<string, mixed>|null
     */
    private function parseJsonFile(string $path): ?array
    {
        $content = file_get_contents($path);
        
        if ($content === false) {
            $this->logger?->warning('Failed to read config file', ['path' => $path]);
            return null;
        }

        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger?->error('Invalid JSON in config file', [
                'path' => $path,
                'error' => json_last_error_msg(),
            ]);
            return null;
        }

        if (!is_array($data)) {
            $this->logger?->warning('Config file did not return an array', ['path' => $path]);
            return null;
        }

        return $data;
    }

    /**
     * Get default route from config.
     * 
     * @return array{module: string, controller: string, action: string}
     */
    public function getDefaultRoute(array $config): array
    {
        $defaultRoute = $config['default_route'] ?? null;

        if ($defaultRoute && is_array($defaultRoute)) {
            return [
                'module' => $defaultRoute['module'] ?? 'home',
                'controller' => $defaultRoute['controller'] ?? 'home',
                'action' => $defaultRoute['action'] ?? 'index',
            ];
        }

        return [
            'module' => 'home',
            'controller' => 'home',
            'action' => 'index',
        ];
    }
}
