<?php

declare(strict_types=1);

namespace Architect\Services\Performance\Contracts;

interface PerformanceMonitorInterface
{
    public function start(): void;
    
    public function collectMetrics(): array;
    
    public function getCollector(): MetricCollectorInterface;
    
    public function getAggregator(): MetricAggregatorInterface;
    
    public function getStorage(): ?MetricStorageInterface;
}