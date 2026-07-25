<?php

declare(strict_types=1);

namespace Architect\Services\Database;

use InvalidArgumentException;

/**
 * Builds DSN strings for various database drivers.
 */
class DsnBuilder
{
    /**
     * Build DSN string from configuration.
     *
     * @param string $driver Driver name (mysql, pgsql, sqlite)
     * @param array $config Connection configuration
     * @return string DSN string
     */
    public function build(string $driver, array $config): string
    {
        return match ($driver) {
            'mysql' => $this->buildMysql($config),
            'pgsql' => $this->buildPgsql($config),
            'sqlite' => $this->buildSqlite($config),
            default => throw new InvalidArgumentException(sprintf('Unsupported driver "%s"', $driver)),
        };
    }

    /**
     * Build MySQL DSN.
     */
    private function buildMysql(array $config): string
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 3306;
        $database = $config['database'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    }

    /**
     * Build PostgreSQL DSN.
     */
    private function buildPgsql(array $config): string
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 5432;
        $database = $config['database'] ?? '';
        $schema = $config['schema'] ?? 'public';

        return "pgsql:host={$host};port={$port};dbname={$database};options='--search_path={$schema}'";
    }

    /**
     * Build SQLite DSN.
     */
    private function buildSqlite(array $config): string
    {
        $database = $config['database'] ?? ':memory:';
        return "sqlite:{$database}";
    }
}
