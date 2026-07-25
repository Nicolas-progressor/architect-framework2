<?php

declare(strict_types=1);

namespace Architect\Services\Debug\Contracts;

use Architect\Services\Debug\Contracts\DebugCollectorInterface;

/**
 * Main debug service interface.
 */
interface DebugInterface
{
    public function isEnabled(): bool;
    public function boot(): void;
    public function startStage(string $stage): void;
    public function endStage(): void;
    public function log(string $message, string $category = 'info', array $context = []): void;
    public function query(string $query, float $duration = 0, array $params = [], string $source = 'database'): void;
    public function cacheHit(string $key): void;
    public function cacheMiss(string $key): void;
    public function cacheSet(string $key): void;
    public function setSessionData(array $session): void;
    public function getLogs(): array;
    public function getQueries(): array;
    public function getCacheStats(): array;
    public function getSessionData(): array;
    public function getStageTimers(): array;
    public function getData(): array;
    public function clear(): void;
    public function render(): void;
    public function renderWidget(): string;
    public function getCollector(): ?DebugCollectorInterface;
}
