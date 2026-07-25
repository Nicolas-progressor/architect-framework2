<?php

declare(strict_types=1);

namespace Architect\Services\Database\Contracts;

use PDO;
use PDOStatement;

/**
 * Interface for database connection service.
 * Provides basic PDO wrapper methods for query execution.
 */
interface DatabaseInterface
{
    /**
     * Execute a SQL query and return PDOStatement.
     *
     * @param string $sql SQL query
     * @param array $bindings Query parameters
     * @return PDOStatement
     */
    public function query(string $sql, array $bindings = []): PDOStatement;

    /**
     * Execute a SQL statement (INSERT, UPDATE, DELETE) and return affected row count.
     *
     * @param string $sql SQL statement
     * @param array $bindings Query parameters
     * @return int Number of affected rows
     */
    public function execute(string $sql, array $bindings = []): int;

    /**
     * Fetch a single row as associative array.
     *
     * @param string $sql SQL query
     * @param array $bindings Query parameters
     * @return array|null Associative array or null if no rows
     */
    public function fetch(string $sql, array $bindings = []): ?array;

    /**
     * Fetch all rows as array of associative arrays.
     *
     * @param string $sql SQL query
     * @param array $bindings Query parameters
     * @return array
     */
    public function fetchAll(string $sql, array $bindings = []): array;

    /**
     * Get the underlying PDO instance.
     *
     * @return PDO
     */
    public function getPdo(): PDO;

    /**
     * Get the connection name.
     *
     * @return string
     */
    public function getConnectionName(): string;

    /**
     * Begin a transaction.
     *
     * @return bool
     */
    public function beginTransaction(): bool;

    /**
     * Commit the active transaction.
     *
     * @return bool
     */
    public function commit(): bool;

    /**
     * Rollback the active transaction.
     *
     * @return bool
     */
    public function rollBack(): bool;

    /**
     * Check if currently inside a transaction.
     *
     * @return bool
     */
    public function inTransaction(): bool;

    /**
     * Execute a callback within a transaction.
     *
     * @param callable $callback
     * @return mixed
     */
    public function transaction(callable $callback): mixed;

    /**
     * Get last inserted ID.
     *
     * @param string|null $sequence Name of the sequence object (for PostgreSQL)
     * @return string|false
     */
    public function lastInsertId(?string $sequence = null): string|false;
}
