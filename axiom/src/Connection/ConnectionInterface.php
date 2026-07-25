<?php

declare(strict_types=1);

namespace Axiom\Orm\Connection;

use PDO;
use PDOStatement;

interface ConnectionInterface
{
    /**
     * Get PDO instance
     */
    public function getPdo(): PDO;

    /**
     * Execute query and return statement
     */
    public function query(string $sql, array $bindings = []): PDOStatement;

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool;

    /**
     * Commit transaction
     */
    public function commit(): bool;

    /**
     * Rollback transaction
     */
    public function rollBack(): bool;

    /**
     * Check if in transaction
     */
    public function inTransaction(): bool;

    /**
     * Get last inserted ID
     */
    public function lastInsertId(?string $sequence = null): string|false;

    /**
     * Get driver name
     */
    public function getDriver(): string;
}
