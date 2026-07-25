<?php

declare(strict_types=1);

namespace Axiom\Orm;

use Axiom\Orm\Connection\ConnectionManager;
use Axiom\Orm\Connection\ConnectionInterface;
use Axiom\Orm\Query\QueryBuilder;

/**
 * Static facade for ORM operations
 */
class Orm
{
    /**
     * Get QueryBuilder instance
     */
    public static function query(?ConnectionInterface $connection = null): QueryBuilder
    {
        return new QueryBuilder($connection);
    }

    /**
     * Get QueryBuilder for specific table
     */
    public static function table(string $table, ?ConnectionInterface $connection = null): QueryBuilder
    {
        return (new QueryBuilder($connection))->from($table);
    }

    /**
     * Execute raw SQL query
     */
    public static function raw(string $sql, array $bindings = [], ?ConnectionInterface $connection = null): QueryBuilder
    {
        return (new QueryBuilder($connection))->raw($sql, $bindings);
    }

    /**
     * Load configuration from file or array
     */
    public static function loadConfig(array|string $config): void
    {
        ConnectionManager::loadConfig($config);
    }

    /**
     * Get connection by name
     */
    public static function connection(string $name = 'default'): ConnectionInterface
    {
        return ConnectionManager::getConnection($name);
    }

    /**
     * Get default connection
     */
    public static function getConnection(): ConnectionInterface
    {
        return ConnectionManager::getDefault();
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return ConnectionManager::beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return ConnectionManager::commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollBack(): bool
    {
        return ConnectionManager::rollBack();
    }

    /**
     * Execute callback in transaction
     */
    public static function transaction(callable $callback): mixed
    {
        return ConnectionManager::transaction($callback);
    }
}
