<?php

declare(strict_types=1);

namespace Architect\Services\Performance\Metrics;

use Architect\Services\Performance\Contracts\MetricAggregatorInterface;

class MetricAggregator implements MetricAggregatorInterface
{
    public function aggregate(array $metrics): array
    {
        $aggregated = [
            'summary' => $this->createSummary($metrics),
            'trends' => $this->calculateTrends($metrics),
            'averages' => $this->calculateAverages($metrics),
            'percentiles' => $this->calculatePercentiles($metrics),
            'bottlenecks' => $this->identifyBottlenecks($metrics),
            'recommendations' => $this->generateRecommendations($metrics),
        ];
        
        return $aggregated;
    }
    
    public function calculateAverages(array $data): array
    {
        $averages = [];
        
        foreach ($data as $key => $values) {
            if (is_array($values) && !empty($values)) {
                if (is_numeric(array_values($values)[0])) {
                    $averages[$key] = array_sum($values) / count($values);
                } elseif (is_array($values[0])) {
                    // Рекурсивный расчет средних для вложенных массивов
                    $averages[$key] = $this->calculateAverages($values);
                }
            }
        }
        
        return $averages;
    }
    
    public function calculatePercentiles(array $data, array $percentiles = [50, 90, 95, 99]): array
    {
        $results = [];
        
        foreach ($data as $key => $values) {
            if (is_array($values) && !empty($values) && is_numeric(array_values($values)[0])) {
                sort($values);
                $results[$key] = [];
                
                foreach ($percentiles as $percentile) {
                    $index = (int) (($percentile / 100) * (count($values) - 1));
                    $results[$key][$percentile] = $values[$index];
                }
            }
        }
        
        return $results;
    }
    
    public function identifyBottlenecks(array $metrics, array $thresholds = []): array
    {
        $bottlenecks = [];
        
        // Проверка времени ответа
        if (isset($metrics['response_time']['current'])) {
            $threshold = $thresholds['response_time_ms'] ?? 500;
            if ($metrics['response_time']['current'] > $threshold) {
                $bottlenecks['response_time'] = [
                    'value' => $metrics['response_time']['current'],
                    'threshold' => $threshold,
                    'excess' => $metrics['response_time']['current'] - $threshold,
                ];
            }
        }
        
        // Проверка использования памяти
        if (isset($metrics['memory_usage']['peak_mb'])) {
            $threshold = $thresholds['memory_mb'] ?? 128;
            if ($metrics['memory_usage']['peak_mb'] > $threshold) {
                $bottlenecks['memory_usage'] = [
                    'value' => $metrics['memory_usage']['peak_mb'],
                    'threshold' => $threshold,
                    'excess' => $metrics['memory_usage']['peak_mb'] - $threshold,
                ];
            }
        }
        
        // Проверка количества запросов к БД
        if (isset($metrics['database_queries']['count'])) {
            $threshold = $thresholds['database_queries'] ?? 50;
            if ($metrics['database_queries']['count'] > $threshold) {
                $bottlenecks['database_queries'] = [
                    'value' => $metrics['database_queries']['count'],
                    'threshold' => $threshold,
                    'excess' => $metrics['database_queries']['count'] - $threshold,
                ];
            }
        }
        
        // Проверка эффективности кэширования
        if (isset($metrics['cache_efficiency']['hit_ratio'])) {
            if ($metrics['cache_efficiency']['hit_ratio'] < 80) {
                $bottlenecks['cache_efficiency'] = [
                    'value' => $metrics['cache_efficiency']['hit_ratio'],
                    'threshold' => 80,
                    'deficit' => 80 - $metrics['cache_efficiency']['hit_ratio'],
                ];
            }
        }
        
        return $bottlenecks;
    }
    
    public function generateRecommendations(array $metrics): array
    {
        $recommendations = [];
        
        // Рекомендации по времени ответа
        if (isset($metrics['response_time']['slowest_stage'])) {
            $slowestStage = $metrics['response_time']['slowest_stage'];
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'response_time',
                'title' => 'Optimize ' . $slowestStage['name'] . ' stage',
                'description' => 'This stage is taking ' . round($slowestStage['duration'] * 1000, 1) . 'ms (' . $slowestStage['percent'] . '% of total time)',
                'impact' => 'Could reduce response time by ' . round($slowestStage['duration'] * 300, 1) . 'ms',
                'solution' => 'Review the code in ' . $slowestStage['name'] . ' stage for optimization opportunities',
            ];
        }
        
        // Рекомендации по памяти
        if (isset($metrics['memory_usage']['threshold_exceeded']) && $metrics['memory_usage']['threshold_exceeded']) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'memory_usage',
                'title' => 'Reduce memory usage',
                'description' => 'Memory usage peaked at ' . $metrics['memory_usage']['peak_mb'] . 'MB, exceeding threshold',
                'impact' => 'Prevent out-of-memory errors and improve stability',
                'solution' => 'Implement memory profiling, use unset() for large variables, consider pagination for large datasets',
            ];
        }
        
        // Рекомендации по БД
        if (isset($metrics['database_queries']['threshold_exceeded']) && $metrics['database_queries']['threshold_exceeded']) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'database',
                'title' => 'Reduce database queries',
                'description' => $metrics['database_queries']['count'] . ' queries executed, consider using eager loading or caching',
                'impact' => 'Could reduce database load by ' . round(($metrics['database_queries']['count'] - 20) / $metrics['database_queries']['count'] * 100) . '%',
                'solution' => 'Implement query caching, use eager loading for relationships, review N+1 query problems',
            ];
        }
        
        // Рекомендации по кэшированию
        if (isset($metrics['cache_efficiency']['hit_ratio']) && $metrics['cache_efficiency']['hit_ratio'] < 80) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'cache',
                'title' => 'Improve cache efficiency',
                'description' => 'Cache hit ratio is ' . $metrics['cache_efficiency']['hit_ratio'] . '%, target is >80%',
                'impact' => 'Could improve response time by ' . round((80 - $metrics['cache_efficiency']['hit_ratio']) * 0.5) . 'ms',
                'solution' => 'Review cache keys, increase TTL for frequently accessed data, implement cache warming',
            ];
        }
        
        return $recommendations;
    }
    
    public function calculatePerformanceScore(array $metrics): int
    {
        $score = 100;
        
        // Вычет за превышение времени ответа
        if (isset($metrics['response_time']['threshold_exceeded']) && $metrics['response_time']['threshold_exceeded']) {
            $current = $metrics['response_time']['current'] ?? 0;
            $threshold = $metrics['response_time']['threshold'] ?? 500;
            $excess = ($current - $threshold) / 100;
            $score -= min(30, $excess * 5);
        }
        
        // Вычет за использование памяти
        if (isset($metrics['memory_usage']['threshold_exceeded']) && $metrics['memory_usage']['threshold_exceeded']) {
            $peak = $metrics['memory_usage']['peak_mb'] ?? 0;
            $threshold = $metrics['memory_usage']['memory_threshold'] ?? 128;
            $excess = ($peak - $threshold) / 10;
            $score -= min(40, $excess * 3);
        }
        
        // Вычет за количество запросов к БД
        if (isset($metrics['database_queries']['threshold_exceeded']) && $metrics['database_queries']['threshold_exceeded']) {
            $count = $metrics['database_queries']['count'] ?? 0;
            $threshold = $metrics['database_queries']['query_threshold'] ?? 50;
            $excess = ($count - $threshold) / 10;
            $score -= min(20, $excess * 2);
        }
        
        // Бонус за эффективность кэширования
        if (isset($metrics['cache_efficiency']['hit_ratio']) && $metrics['cache_efficiency']['hit_ratio'] > 90) {
            $score += 5;
        }
        
        return max(0, min(100, round($score)));
    }
    
    private function createSummary(array $metrics): array
    {
        return [
            'total_time' => $metrics['response_time']['current'] ?? 0,
            'peak_memory' => $metrics['memory_usage']['peak_mb'] ?? 0,
            'db_queries' => $metrics['database_queries']['count'] ?? 0,
            'cache_hit_ratio' => $metrics['cache_efficiency']['hit_ratio'] ?? 0,
            'performance_score' => $this->calculatePerformanceScore($metrics),
        ];
    }
    
    private function calculateTrends(array $metrics): array
    {
        // В реальной реализации здесь будет логика расчета трендов
        return [
            'response_time_trend' => 'stable',
            'memory_trend' => 'stable',
            'db_queries_trend' => 'stable',
        ];
    }
}