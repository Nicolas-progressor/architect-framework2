<?php

declare(strict_types=1);

namespace Architect\Core\Contracts;

/**
 * Statement interface for lifecycle hooks.
 */
interface StatementInterface
{
    /**
     * Register a callback for a statement.
     */
    public function on(string $statement, callable $callback, int $priority = 10): void;

    /**
     * Run all callbacks for a statement.
     */
    public function run(string $statement): void;

    /**
     * Run all statements in order.
     */
    public function runAll(): void;

    /**
     * Get all available statement names.
     */
    public function getStatements(): array;

    /**
     * Check if statement was executed.
     */
    public function isExecuted(string $statement): bool;
}
