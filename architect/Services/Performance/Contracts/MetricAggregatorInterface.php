<?php

declare(strict_types=1);

namespace Architect\Services\Performance\Contracts;

interface MetricAggregatorInterface
{
    public function aggregate(array $metrics): array;
    
    public function calculateAverages(array $data): array;
    
    public function calculatePercentiles(array $data, array $percentiles = [50, 90, 95, 99]): array;
    
    public function identifyBottlenecks(array $metrics, array $thresholds = []): array;
    
    public function generateRecommendations(array $metrics): array;
    
    public function calculatePerformanceScore(array $metrics): int;
}