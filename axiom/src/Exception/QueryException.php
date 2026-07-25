<?php

declare(strict_types=1);

namespace Axiom\Orm\Exception;

class QueryException extends AxiomException
{
    /**
     * Create exception for invalid query
     */
    public static function invalidQuery(string $message): self
    {
        return new self("Invalid query: {$message}");
    }

    /**
     * Create exception for missing table
     */
    public static function missingTable(): self
    {
        return new self("Table name is required. Use from() or table() method.");
    }

    /**
     * Create exception for invalid binding
     */
    public static function invalidBinding(mixed $value): self
    {
        $type = gettype($value);
        return new self("Invalid binding type: {$type}. Use scalar values or arrays.");
    }

    /**
     * Create exception for execute failure
     */
    public static function executionFailed(string $sql, \Throwable $previous): self
    {
        return new self("Query execution failed: {$sql}", 0, $previous);
    }
}
