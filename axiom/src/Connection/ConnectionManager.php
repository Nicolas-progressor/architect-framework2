<?php

declare(strict_types=1);

namespace Axiom\Orm\Connection;

use Axiom\Orm\Exception\ConnectionException;

class ConnectionManager
{
    /** @var array<string, Connection> */
    private static array $connections = [];

    /** @var array<string, array> */
    private static array $config = [];

    private static ?string $defaultConnection = null;
    private static bool $configured = false;

    /** @var callable|null */
    private static $queryCallback = null;

    /**
     * Configure connections from array or JSON file
     */
    public static function configure(array|string $config): void
    {
        if (is_string($config)) {
            if (!file_exists($config)) {
                throw ConnectionException::missingConfig($config);
            }
            $config = json_decode(file_get_contents($config), true);
        }

        self::$config = $config['connections'] ?? $config;
        self::$defaultConnection = $config['default'] ?? 'default';
        self::$configured = true;
    }

    /**
     * Check if already configured
     */
    public static function isConfigured(): bool
    {
        return self::$configured;
    }

    /**
     * Load configuration from array or JSON file (alias for configure)
     */
    public static function loadConfig(array|string $config): void
    {
        self::configure($config);
    }

    /**
     * Get configuration for a connection
     */
    public static function getConfig(string $name = 'default'): array
    {
        if (!isset(self::$config[$name])) {
            throw ConnectionException::missingConfig($name);
        }
        return self::$config[$name];
    }

    /**
     * Get or create connection by name
     */
    public static function getConnection(string $name = 'default'): Connection
    {
        if (!isset(self::$connections[$name])) {
            $config = self::getConfig($name);
            self::$connections[$name] = new Connection($config, $name);
        }

        return self::$connections[$name];
    }

    /**
     * Get default connection name
     */
    public static function getDefaultConnection(): string
    {
        return self::$defaultConnection ?? 'default';
    }

    /**
     * Get default connection instance
     */
    public static function getDefault(): Connection
    {
        return self::getConnection(self::getDefaultConnection());
    }

    /**
     * Get PDO instance from default connection
     */
    public static function getPdo(): \PDO
    {
        return self::getDefault()->getPdo();
    }

    /**
     * Disconnect specific connection
     */
    public static function disconnect(string $name = 'default'): void
    {
        unset(self::$connections[$name]);
    }

    /**
     * Disconnect all connections
     */
    public static function disconnectAll(): void
    {
        self::$connections = [];
    }

    /**
     * Check if connection exists
     */
    public static function hasConnection(string $name = 'default'): bool
    {
        return isset(self::$connections[$name]);
    }

    /**
     * Get all connection names
     * @return string[]
     */
    public static function getConnectionNames(): array
    {
        return array_keys(self::$config);
    }

    /**
     * Begin transaction on default connection
     */
    public static function beginTransaction(): bool
    {
        return self::getDefault()->beginTransaction();
    }

    /**
     * Commit transaction on default connection
     */
    public static function commit(): bool
    {
        return self::getDefault()->commit();
    }

    /**
     * Rollback transaction on default connection
     */
    public static function rollBack(): bool
    {
        return self::getDefault()->rollBack();
    }

    /**
     * Execute callback in transaction
     */
    public static function transaction(callable $callback): mixed
    {
        self::beginTransaction();
        try {
            $result = $callback();
            self::commit();
            return $result;
        } catch (\Throwable $e) {
            self::rollBack();
            throw $e;
        }
    }
    
    /**
     * Set query callback for logging (e.g., to Debug service)
     * Callback receives: string $sql, float $duration, array $params
     */
    public static function setQueryCallback(callable $callback): void
    {
        self::$queryCallback = $callback;
    }
    
    /**
     * Get query callback
     */
    public static function getQueryCallback(): ?callable
    {
        return self::$queryCallback;
    }
    
    /**
     * Invoke query callback if set
     */
    public static function logQuery(string $sql, float $duration, array $params = []): void
    {
        if (self::$queryCallback !== null) {
            (self::$queryCallback)($sql, $duration, $params);
        }
    }
}
