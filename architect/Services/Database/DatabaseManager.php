<?php

declare(strict_types=1);

namespace Architect\Services\Database;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Services\Database\Contracts\QueryLoggerInterface;
use Architect\Support\AbstractService;
use InvalidArgumentException;

/**
 * Manager for database connections.
 * Loads configuration from database.json and provides access to connections.
 */
class DatabaseManager extends AbstractService
{
    /** @var array<string, Database> */
    private array $connections = [];

    /** @var array<string, array> */
    private array $config = [];

    private ?string $defaultConnection = null;
    private bool $configured = false;

    private ?QueryLoggerInterface $queryLogger = null;
    private ?DsnBuilder $dsnBuilder = null;

    /**
     * Create database manager.
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    /**
     * Configure connections from array or JSON file path.
     *
     * @param array|string $config Configuration array or path to JSON file
     */
    public function configure(array|string $config): void
    {
        if (is_string($config)) {
            if (!file_exists($config)) {
                throw new InvalidArgumentException(sprintf('Database configuration file not found: %s', $config));
            }
            $config = json_decode(file_get_contents($config), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Invalid JSON in database configuration file');
            }
        }

        $this->config = $config['connections'] ?? $config;
        $this->defaultConnection = $config['default'] ?? 'default';
        $this->configured = true;
    }

    /**
     * Check if manager is configured.
     */
    public function isConfigured(): bool
    {
        return $this->configured;
    }

    /**
     * Get configuration for a connection.
     */
    public function getConfig(string $name = 'default'): array
    {
        if (!isset($this->config[$name])) {
            throw new InvalidArgumentException(sprintf('Database connection "%s" is not configured', $name));
        }
        return $this->config[$name];
    }

    /**
     * Get a database connection by name.
     * If $name is null, the default connection name from configuration is used.
     */
    public function connection(?string $name = null): Database
    {
        $name ??= $this->getDefaultConnectionName();
        if (!isset($this->connections[$name])) {
            $config = $this->getConfig($name);
            $this->connections[$name] = new Database(
                $config,
                $name,
                $this->queryLogger,
                $this->dsnBuilder
            );
        }

        return $this->connections[$name];
    }

    /**
     * Get the default connection name.
     */
    public function getDefaultConnectionName(): string
    {
        return $this->defaultConnection ?? 'default';
    }

    /**
     * Get the default connection instance.
     */
    public function getDefaultConnection(): Database
    {
        return $this->connection($this->getDefaultConnectionName());
    }

    /**
     * Get PDO instance from default connection.
     */
    public function getPdo(): \PDO
    {
        return $this->getDefaultConnection()->getPdo();
    }

    /**
     * Disconnect a specific connection.
     */
    public function disconnect(string $name = 'default'): void
    {
        unset($this->connections[$name]);
    }

    /**
     * Disconnect all connections.
     */
    public function disconnectAll(): void
    {
        $this->connections = [];
    }

    /**
     * Check if a connection exists.
     */
    public function hasConnection(string $name = 'default'): bool
    {
        return isset($this->connections[$name]);
    }

    /**
     * Get all configured connection names.
     *
     * @return string[]
     */
    public function getConnectionNames(): array
    {
        return array_keys($this->config);
    }

    /**
     * Begin transaction on default connection.
     */
    public function beginTransaction(): bool
    {
        return $this->getDefaultConnection()->beginTransaction();
    }

    /**
     * Commit transaction on default connection.
     */
    public function commit(): bool
    {
        return $this->getDefaultConnection()->commit();
    }

    /**
     * Rollback transaction on default connection.
     */
    public function rollBack(): bool
    {
        return $this->getDefaultConnection()->rollBack();
    }

    /**
     * Execute a callback within a transaction on default connection.
     */
    public function transaction(callable $callback): mixed
    {
        return $this->getDefaultConnection()->transaction($callback);
    }

    /**
     * Set a query logger for all connections.
     */
    public function setQueryLogger(?QueryLoggerInterface $queryLogger): void
    {
        $this->queryLogger = $queryLogger;
        foreach ($this->connections as $connection) {
            $connection->setQueryLogger($queryLogger);
        }
    }

    /**
     * Set a DSN builder for all connections.
     */
    public function setDsnBuilder(?DsnBuilder $dsnBuilder): void
    {
        $this->dsnBuilder = $dsnBuilder;
        // Future connections will use this builder; existing connections would need to be recreated.
        // Optionally, we could update existing connections, but that's more complex.
        // For simplicity, we only affect new connections.
    }

    /**
     * Set a global query callback for all connections.
     * @deprecated Use setQueryLogger with a QueryLoggerInterface implementation instead.
     */
    public function setQueryCallback(callable $callback): void
    {
        // Create a simple logger that wraps the callback
        $logger = new class ($callback) implements QueryLoggerInterface {
            private $callback;
            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }
            public function logQuery(string $sql, float $duration, array $bindings = []): void
            {
                ($this->callback)($sql, $duration, $bindings);
            }
        };
        $this->setQueryLogger($logger);
    }
}
