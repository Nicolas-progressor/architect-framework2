<?php

declare(strict_types=1);

namespace Axiom\Orm\Exception;

class ConnectionException extends AxiomException
{
    /**
     * Create exception for failed connection
     */
    public static function connectionFailed(string $driver, string $host, ?\Throwable $previous = null): self
    {
        return new self(
            "Failed to connect to database. Driver: {$driver}, Host: {$host}",
            0,
            $previous
        );
    }

    /**
     * Create exception for missing configuration
     */
    public static function missingConfig(string $connectionName): self
    {
        return new self("Connection configuration not found: {$connectionName}");
    }

    /**
     * Create exception for invalid driver
     */
    public static function invalidDriver(string $driver): self
    {
        return new self("Invalid database driver: {$driver}. Supported: mysql, pgsql, sqlite");
    }
}
