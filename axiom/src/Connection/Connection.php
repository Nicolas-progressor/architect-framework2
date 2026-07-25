<?php

declare(strict_types=1);

namespace Axiom\Orm\Connection;

use Axiom\Orm\Exception\ConnectionException;
use PDO;
use PDOException;
use PDOStatement;

class Connection implements ConnectionInterface
{
    private PDO $pdo;
    private string $driver;
    private string $name;

    /**
     * Create new connection
     */
    public function __construct(array $config, string $name = 'default')
    {
        $this->name = $name;
        $this->pdo = $this->createPdo($config);
        $this->driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Create PDO instance from config
     */
    private function createPdo(array $config): PDO
    {
        $driver = $config['driver'] ?? 'mysql';
        $dsn = $this->buildDsn($driver, $config);

        $options = $config['options'] ?? [];
        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $options = array_replace($defaultOptions, $options);

        try {
            return new PDO($dsn, $config['username'] ?? '', $config['password'] ?? '', $options);
        } catch (PDOException $e) {
            throw ConnectionException::connectionFailed(
                $driver,
                $config['host'] ?? 'unknown',
                $e
            );
        }
    }

    /**
     * Build DSN string from config
     */
    private function buildDsn(string $driver, array $config): string
    {
        return match ($driver) {
            'mysql' => $this->buildMysqlDsn($config),
            'pgsql' => $this->buildPgsqlDsn($config),
            'sqlite' => $this->buildSqliteDsn($config),
            default => throw ConnectionException::invalidDriver($driver),
        };
    }

    private function buildMysqlDsn(array $config): string
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 3306;
        $database = $config['database'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    }

    private function buildPgsqlDsn(array $config): string
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 5432;
        $database = $config['database'] ?? '';
        $schema = $config['schema'] ?? 'public';

        return "pgsql:host={$host};port={$port};dbname={$database};options='--search_path={$schema}'";
    }

    private function buildSqliteDsn(array $config): string
    {
        $database = $config['database'] ?? ':memory:';
        return "sqlite:{$database}";
    }

    /**
     * Get PDO instance
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute query
     */
    public function query(string $sql, array $bindings = []): PDOStatement
    {
        $startTime = microtime(true);
        
        if (empty($bindings)) {
            $stmt = $this->pdo->query($sql);
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bindings);
        }

        $duration = microtime(true) - $startTime;
        
        // Log query if callback is set
        ConnectionManager::logQuery($sql, $duration, $bindings);
        
        return $stmt;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Check if in transaction
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Get last inserted ID
     */
    public function lastInsertId(?string $sequence = null): string|false
    {
        return $this->pdo->lastInsertId($sequence);
    }

    /**
     * Get driver name
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Get connection name
     */
    public function getName(): string
    {
        return $this->name;
    }
}
