<?php

declare(strict_types=1);

namespace Architect\Contracts\Core;

interface StatementInterface
{
    public function on(string $statement, callable $callback, int $priority = 10): void;
    public function run(string $statement): void;
    public function runAll(): void;
    public function getStatements(): array;
    public function isExecuted(string $statement): bool;
}
