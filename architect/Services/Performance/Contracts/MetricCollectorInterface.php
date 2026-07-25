<?php

declare(strict_types=1);

namespace Architect\Services\Performance\Contracts;

interface MetricCollectorInterface
{
    public function start(): void;

    public function startStage(string $stage): void;

    public function endStage(): void;

    public function recordDatabaseQuery(string $sql, float $duration, array $params = []): void;

    public function recordCacheOperation(string $operation, string $key, bool $hit = false): void;

    public function recordTemplateCompilation(string $template, float $duration): void;

    public function recordServiceLoading(string $service, float $duration): void;

    public function getStageTimings(): array;

    public function getDatabaseQueries(): array;

    public function getCacheStats(): array;

    public function getBlueprintData(): array;

    public function getServiceMetrics(): array;

    public function getMemoryUsage(): array;
}
