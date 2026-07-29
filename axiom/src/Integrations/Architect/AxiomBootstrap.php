<?php

declare(strict_types=1);

namespace Axiom\Orm\Integrations\Architect;

use Axiom\Orm\Connection\ConnectionManager;
use Architect\Contracts\Core\ContainerInterface;
use Architect\Contracts\Core\EnvironmentInterface;

/**
 * Bootstrap Axiom ORM using Architect Environment configuration.
 */
class AxiomBootstrap
{
    /**
     * Bootstrap Axiom ORM with configuration from Architect.
     */
    public static function bootstrap(EnvironmentInterface $environment, ?ContainerInterface $container = null): void
    {
        // Setup query logging FIRST - before any config checks
        // This ensures we capture all queries even if config loading fails later
        if ($container !== null) {
            self::setupQueryLogging($container);
        }
        
        $config = [];
        
        // Try to get config from EnvironmentManager (priority)
        $envDbConfig = $environment->get('database');

        if ($envDbConfig && isset($envDbConfig['connections'])) {
            $config = [
                'default' => $envDbConfig['default'] ?? 'mysql',
                'connections' => $envDbConfig['connections']
            ];
        }

        // Fallback: load from file
        if (empty($config)) {
            $config = self::loadDatabaseConfig();
        }
        
        if (empty($config)) {
            return;
        }
        
        // Apply environment variables (highest priority)
        $config = self::applyEnvironmentVariables($config);

        // Configure Axiom ConnectionManager
        ConnectionManager::configure($config);
    }

    /**
     * Setup query logging to Architect Debug service.
     */
    private static function setupQueryLogging(ContainerInterface $container): void
    {
        error_log('[AxiomBootstrap.setupQueryLogging] START');
        try {
            $hasDebug = $container->has('debug');
            error_log('[AxiomBootstrap.setupQueryLogging] has debug: ' . ($hasDebug ? 'YES' : 'NO'));
            
            if (!$hasDebug) {
                return;
            }

            $debug = $container->get('debug');
            error_log('[AxiomBootstrap.setupQueryLogging] Debug service: ' . get_class($debug));
            
            // Don't check isEnabled() here - Debug::query() already checks internally
            // This allows callback to be set before boot() is called
            ConnectionManager::setQueryCallback(function(string $sql, float $duration, array $params) use ($debug) {
                error_log('[AxiomBootstrap] Query callback FIRED: ' . substr($sql, 0, 50));
                $debug->query($sql, $duration, $params, 'axiom');
            });
            
            error_log('[AxiomBootstrap.setupQueryLogging] Query callback SET');
        } catch (\Throwable $e) {
            error_log('[AxiomBootstrap.setupQueryLogging] Error: ' . $e->getMessage());
            // Ignore errors - debug is not critical
        }
    }
    
    /**
     * Load base database configuration from app/config/database.json.
     */
    protected static function loadDatabaseConfig(): array
    {
        $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__, 4);

        $configPaths = [
            $root . '/app/config/database.json',
            $root . '/config/database.json',
            $root . '/axiom/config/database.json',
        ];

        foreach ($configPaths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $config = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE && !empty($config)) {
                    return $config;
                }
            }
        }

        return [];
    }

    /**
     * Apply environment variables to configuration.
     */
    protected static function applyEnvironmentVariables(array $config): array
    {
        $connection = $config['default'] ?? 'mysql';

        $envConnection = getenv('DB_CONNECTION');
        if ($envConnection) {
            $connection = $envConnection;

            if (!isset($config['connections'][$connection])) {
                $config['connections'][$connection] = [];
            }
        }
        
        $envMappings = [
            'DB_HOST' => 'host',
            'DB_PORT' => 'port',
            'DB_DATABASE' => 'database',
            'DB_USERNAME' => 'username',
            'DB_PASSWORD' => 'password',
            'DB_CHARSET' => 'charset',
            'DB_PREFIX' => 'prefix',
        ];

        foreach ($envMappings as $envVar => $configKey) {
            $value = getenv($envVar);
            if ($value !== false) {
                $config['connections'][$connection][$configKey] = $value;
            }
        }

        $config['default'] = $connection;

        return $config;
    }

    /**
     * Get database configuration for a specific connection.
     */
    public static function getConnectionConfig(string $name = 'default'): ?array
    {
        try {
            return ConnectionManager::getConfig($name);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Check if Axiom ORM is configured.
     */
    public static function isConfigured(): bool
    {
        try {
            ConnectionManager::getDefault();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get PDO instance for console commands.
     */
    public static function getPdo(): \PDO
    {
        return ConnectionManager::getPdo();
    }
}
