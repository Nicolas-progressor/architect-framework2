<?php

declare(strict_types=1);

namespace Architect\Services\Database\Contracts;

/**
 * Interface for query logging.
 */
interface QueryLoggerInterface
{
    /**
     * Log a database query.
     *
     * @param string $sql SQL query
     * @param float $duration Execution time in seconds
     * @param array $bindings Query parameters
     */
    public function logQuery(string $sql, float $duration, array $bindings = []): void;
}
