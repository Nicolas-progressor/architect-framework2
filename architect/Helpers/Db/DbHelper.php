<?php

declare(strict_types=1);

namespace Architect\Helpers\Db;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Helpers\Core\AbstractHelper;
use PDO;
use PDOStatement;

/**
 * Database static helper.
 * Provides convenient static‑like access to database operations.
 *
 * Usage:
 *   DB::query('SELECT * FROM users');
 *   DB::fetch('SELECT * FROM users WHERE id = ?', [1]);
 *   DB::execute('INSERT INTO users (name) VALUES (?)', ['John']);
 *   DB::transaction(function () { ... });
 */
class DbHelper extends AbstractHelper
{
    private ContainerInterface $container;

    /**
     * Create DB helper with container.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get the database manager instance.
     */
    private function db(): \Architect\Services\Database\DatabaseManager
    {
        return $this->container->get('database');
    }

    /**
     * Get the default database connection.
     */
    private function connection(): \Architect\Services\Database\Database
    {
        return $this->db()->getDefaultConnection();
    }

    /**
     * Execute a SQL query and return PDOStatement.
     */
    public function query(string $sql, array $bindings = []): PDOStatement
    {
        return $this->connection()->query($sql, $bindings);
    }

    /**
     * Execute a SQL statement and return affected row count.
     */
    public function execute(string $sql, array $bindings = []): int
    {
        return $this->connection()->execute($sql, $bindings);
    }

    /**
     * Fetch a single row as associative array.
     */
    public function fetch(string $sql, array $bindings = []): ?array
    {
        return $this->connection()->fetch($sql, $bindings);
    }

    /**
     * Fetch all rows as array of associative arrays.
     */
    public function fetchAll(string $sql, array $bindings = []): array
    {
        return $this->connection()->fetchAll($sql, $bindings);
    }

    /**
     * Get the underlying PDO instance of the default connection.
     */
    public function getPdo(): PDO
    {
        return $this->connection()->getPdo();
    }

    /**
     * Begin a transaction on the default connection.
     */
    public function beginTransaction(): bool
    {
        return $this->connection()->beginTransaction();
    }

    /**
     * Commit the active transaction.
     */
    public function commit(): bool
    {
        return $this->connection()->commit();
    }

    /**
     * Rollback the active transaction.
     */
    public function rollBack(): bool
    {
        return $this->connection()->rollBack();
    }

    /**
     * Check if currently inside a transaction.
     */
    public function inTransaction(): bool
    {
        return $this->connection()->inTransaction();
    }

    /**
     * Execute a callback within a transaction.
     */
    public function transaction(callable $callback): mixed
    {
        return $this->connection()->transaction($callback);
    }

    /**
     * Get last inserted ID.
     */
    public function lastInsertId(?string $sequence = null): string|false
    {
        return $this->connection()->lastInsertId($sequence);
    }

    /**
     * Get a connection by name.
     */
    public function connectionName(string $name): \Architect\Services\Database\Database
    {
        return $this->db()->connection($name);
    }

    /**
     * Get the name of the default connection.
     */
    public function getConnectionName(): string
    {
        return $this->connection()->getConnectionName();
    }
}
