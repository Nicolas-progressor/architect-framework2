<?php

declare(strict_types=1);

namespace Architect\Services\Performance;

use Architect\Core\Container;
use Architect\Services\Performance\Contracts\PerformanceMonitorInterface;
use Architect\Services\Performance\Metrics\MetricCollector;
use Architect\Services\Performance\Metrics\MetricAggregator;
use Architect\Services\Performance\Storage\MetricStorageInterface;

class PerformanceMonitor implements PerformanceMonitorInterface
{
    private Container $container;
    private MetricCollector $collector;
    private MetricAggregator $aggregator;
    private ?MetricStorageInterface $storage = null;
    private array $config = [];
    private bool $enabled = false;
    private float $startTime;
    private array $metrics = [];
    private array $thresholds = [];
    
    public function __construct(Container $container, array $config = [])
    {
        $this->container = $container;
        $this->config = $config;
        $this->collector = new MetricCollector($container);
        $this->aggregator = new MetricAggregator();
        $this->thresholds = $config['thresholds'] ?? [];
        $this->enabled = $config['enabled'] ?? true;
        
        $this->initializeStorage();
    }
    
    public function start(): void
    {
        if (!$this->enabled) {
            return;
        }
        
        $this->startTime = microtime(true);
        $this->collector->start();
        
        // Начать сбор метрик
        $this->startResponseTimeMeasurement();
        $this->startMemoryMeasurement();
        $this->startDatabaseMonitoring();
        $this->startCacheMonitoring();
        $this->startTemplateMonitoring();
        $this->startServiceMonitoring();
    }
    
    public function collectMetrics(): array
    {
        if (!$this->enabled) {
            return [];
        }
        
        $metrics = [
            'collection_start' => $this->startTime,
            'collection_end' => microtime(true),
            'response_time' => $this->collectResponseTimeMetrics(),
            'memory_usage' => $this->collectMemoryMetrics(),
            'database_queries' => $this->collectDatabaseMetrics(),
            'cache_efficiency' => $this->collectCacheMetrics(),
            'template_rendering' => $this->collectTemplateMetrics(),
            'service_loading' => $this->collectServiceMetrics(),
            'system_metrics' => $this->collectSystemMetrics(),
        ];
        
        // Анализ метрик
        $metrics['analysis'] = $this->analyzeMetrics($metrics);
        $metrics['recommendations'] = $this->generateRecommendations($metrics);
        
        // Сохранение метрик
        if ($this->storage !== null) {
            $this->storage->store($metrics);
        }
        
        return $metrics;
    }
    
    public function getCollector(): MetricCollector
    {
        return $this->collector;
    }
    
    public function getAggregator(): MetricAggregator
    {
        return $this->aggregator;
    }
    
    public function getStorage(): ?MetricStorageInterface
    {
        return $this->storage;
    }
    
    private function collectResponseTimeMetrics(): array
    {
        $totalTime = microtime(true) - $this->startTime;
        $stages = $this->collector->getStageTimings();
        
        return [
            'current' => round($totalTime * 1000, 1), // ms
            'average' => $this->calculateAverageResponseTime(),
            'peak' => $this->getPeakResponseTime(),
            'stages' => $stages,
            'slowest_stage' => $this->identifySlowestStage($stages),
            'threshold_exceeded' => ($totalTime * 1000) > ($this->thresholds['response_time_ms'] ?? 500),
        ];
    }
    
    private function collectMemoryMetrics(): array
    {
        $current = memory_get_usage();
        $peak = memory_get_peak_usage();
        $limit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        return [
            'current_bytes' => $current,
            'current_mb' => round($current / 1024 / 1024, 2),
            'peak_bytes' => $peak,
            'peak_mb' => round($peak / 1024 / 1024, 2),
            'limit_bytes' => $limit,
            'limit_mb' => round($limit / 1024 / 1024, 2),
            'usage_percent' => $limit > 0 ? round(($peak / $limit) * 100, 1) : 0,
            'threshold_exceeded' => ($peak / 1024 / 1024) > ($this->thresholds['memory_mb'] ?? 128),
        ];
    }
    
    private function collectDatabaseMetrics(): array
    {
        $queries = $this->collector->getDatabaseQueries();
        $slowThreshold = $this->thresholds['slow_query_ms'] ?? 100;
        
        $slowQueries = array_filter($queries, fn($q) => ($q['duration'] ?? 0) > $slowThreshold);
        
        return [
            'count' => count($queries),
            'slow_count' => count($slowQueries),
            'total_time' => array_sum(array_column($queries, 'duration')),
            'average_time' => count($queries) > 0 ? array_sum(array_column($queries, 'duration')) / count($queries) : 0,
            'slow_queries' => array_values($slowQueries),
            'threshold_exceeded' => count($queries) > ($this->thresholds['database_queries'] ?? 50),
        ];
    }
    
    private function collectCacheMetrics(): array
    {
        $stats = $this->collector->getCacheStats();
        $hits = $stats['hits'] ?? 0;
        $misses = $stats['misses'] ?? 0;
        $total = $hits + $misses;
        
        return [
            'hits' => $hits,
            'misses' => $misses,
            'hit_ratio' => $total > 0 ? round(($hits / $total) * 100, 1) : 0,
            'operations' => $stats['operations'] ?? [],
            'size' => $this->collector->getCacheSize(),
            'efficiency' => $this->calculateCacheEfficiency($stats),
        ];
    }
    
    private function collectTemplateMetrics(): array
    {
        $blueprintData = $this->collector->getBlueprintData();
        
        return [
            'count' => count($blueprintData['templates'] ?? []),
            'compiled' => count($blueprintData['compilations'] ?? []),
            'cached' => count($blueprintData['cache'] ?? []),
            'total_time' => array_sum(array_column($blueprintData['compilations'] ?? [], 'duration')),
            'average_time' => $this->calculateAverageTemplateTime($blueprintData),
            'cache_efficiency' => $this->calculateTemplateCacheEfficiency($blueprintData),
        ];
    }
    
    private function collectServiceMetrics(): array
    {
        $services = $this->collector->getServiceMetrics();
        
        return [
            'count' => count($services),
            'loaded' => array_filter($services, fn($s) => $s['loaded']),
            'loading_times' => array_column($services, 'loading_time'),
            'total_loading_time' => array_sum(array_column($services, 'loading_time')),
            'dependencies' => $this->analyzeServiceDependencies($services),
        ];
    }
    
    private function collectSystemMetrics(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'opcache_enabled' => extension_loaded('opcache') && opcache_get_status() !== false,
            'opcache_status' => extension_loaded('opcache') ? opcache_get_status() : null,
            'extensions' => get_loaded_extensions(),
            'ini_settings' => [
                'max_execution_time' => ini_get('max_execution_time'),
                'max_input_time' => ini_get('max_input_time'),
                'memory_limit' => ini_get('memory_limit'),
                'post_max_size' => ini_get('post_max_size'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
            ],
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
        ];
    }
    
    private function analyzeMetrics(array $metrics): array
    {
        $analysis = [
            'bottlenecks' => [],
            'optimization_opportunities' => [],
            'performance_score' => 0,
            'health_status' => 'healthy',
        ];
        
        // Анализ времени ответа
        if ($metrics['response_time']['threshold_exceeded']) {
            $analysis['bottlenecks'][] = 'response_time';
            $analysis['health_status'] = 'warning';
        }
        
        // Анализ использования памяти
        if ($metrics['memory_usage']['threshold_exceeded']) {
            $analysis['bottlenecks'][] = 'memory_usage';
            $analysis['health_status'] = 'critical';
        }
        
        // Анализ запросов к БД
        if ($metrics['database_queries']['threshold_exceeded']) {
            $analysis['optimization_opportunities'][] = 'database_optimization';
        }
        
        // Анализ кэширования
        if ($metrics['cache_efficiency']['hit_ratio'] < 80) {
            $analysis['optimization_opportunities'][] = 'cache_optimization';
        }
        
        // Расчет performance score (0-100)
        $analysis['performance_score'] = $this->calculatePerformanceScore($metrics);
        
        return $analysis;
    }
    
    private function generateRecommendations(array $metrics): array
    {
        $recommendations = [];
        
        // Рекомендации по времени ответа
        if ($metrics['response_time']['threshold_exceeded']) {
            $slowestStage = $metrics['response_time']['slowest_stage'] ?? null;
            if ($slowestStage) {
                $recommendations[] = [
                    'priority' => 'high',
                    'title' => 'Optimize ' . $slowestStage['name'] . ' stage',
                    'description' => 'This stage is taking ' . $slowestStage['duration'] . 'ms (' . $slowestStage['percent'] . '% of total time)',
                    'impact' => 'Could reduce response time by ' . round($slowestStage['duration'] * 0.3, 1) . 'ms',
                    'solution' => 'Review the code in ' . $slowestStage['name'] . ' stage for optimization opportunities',
                ];
            }
        }
        
        // Рекомендации по памяти
        if ($metrics['memory_usage']['threshold_exceeded']) {
            $recommendations[] = [
                'priority' => 'high',
                'title' => 'Reduce memory usage',
                'description' => 'Memory usage peaked at ' . $metrics['memory_usage']['peak_mb'] . 'MB, exceeding threshold of ' . ($this->thresholds['memory_mb'] ?? 128) . 'MB',
                'impact' => 'Prevent out-of-memory errors and improve stability',
                'solution' => 'Implement memory profiling, use unset() for large variables, consider pagination for large datasets',
            ];
        }
        
        // Рекомендации по БД
        if ($metrics['database_queries']['threshold_exceeded']) {
            $recommendations[] = [
                'priority' => 'medium',
                'title' => 'Reduce database queries',
                'description' => $metrics['database_queries']['count'] . ' queries executed, consider using eager loading or caching',
                'impact' => 'Could reduce database load by ' . round(($metrics['database_queries']['count'] - 20) / $metrics['database_queries']['count'] * 100) . '%',
                'solution' => 'Implement query caching, use eager loading for relationships, review N+1 query problems',
            ];
        }
        
        // Рекомендации по кэшированию
        if ($metrics['cache_efficiency']['hit_ratio'] < 80) {
            $recommendations[] = [
                'priority' => 'medium',
                'title' => 'Improve cache efficiency',
                'description' => 'Cache hit ratio is ' . $metrics['cache_efficiency']['hit_ratio'] . '%, target is >80%',
                'impact' => 'Could improve response time by ' . round((80 - $metrics['cache_efficiency']['hit_ratio']) * 0.5) . 'ms',
                'solution' => 'Review cache keys, increase TTL for frequently accessed data, implement cache warming',
            ];
        }
        
        return $recommendations;
    }
    
    private function calculatePerformanceScore(array $metrics): int
    {
        $score = 100;
        
        // Вычет за превышение времени ответа
        if ($metrics['response_time']['threshold_exceeded']) {
            $excess = ($metrics['response_time']['current'] - ($this->thresholds['response_time_ms'] ?? 500)) / 100;
            $score -= min(30, $excess * 5);
        }
        
        // Вычет за использование памяти
        if ($metrics['memory_usage']['threshold_exceeded']) {
            $excess = ($metrics['memory_usage']['peak_mb'] - ($this->thresholds['memory_mb'] ?? 128)) / 10;
            $score -= min(40, $excess * 3);
        }
        
        // Вычет за количество запросов к БД
        if ($metrics['database_queries']['threshold_exceeded']) {
            $excess = ($metrics['database_queries']['count'] - ($this->thresholds['database_queries'] ?? 50)) / 10;
            $score -= min(20, $excess * 2);
        }
        
        // Бонус за эффективность кэширования
        if ($metrics['cache_efficiency']['hit_ratio'] > 90) {
            $score += 5;
        }
        
        return max(0, min(100, round($score)));
    }
    
    private function calculateAverageResponseTime(): float
    {
        // В реальной реализации здесь будет логика расчета среднего времени
        return 0;
    }
    
    private function getPeakResponseTime(): float
    {
        // В реальной реализации здесь будет логика получения пикового времени
        return 0;
    }
    
    private function identifySlowestStage(array $stages): ?array
    {
        if (empty($stages)) {
            return null;
        }
        
        $slowest = null;
        $maxDuration = 0;
        $totalTime = array_sum(array_column($stages, 'duration'));
        
        foreach ($stages as $stage) {
            if ($stage['duration'] > $maxDuration) {
                $maxDuration = $stage['duration'];
                $slowest = $stage;
            }
        }
        
        if ($slowest) {
            $slowest['percent'] = $totalTime > 0 ? round(($slowest['duration'] / $totalTime) * 100, 1) : 0;
        }
        
        return $slowest;
    }
    
    private function calculateCacheEfficiency(array $stats): float
    {
        $hits = $stats['hits'] ?? 0;
        $misses = $stats['misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 1) : 0;
    }
    
    private function calculateAverageTemplateTime(array $blueprintData): float
    {
        $compilations = $blueprintData['compilations'] ?? [];
        if (empty($compilations)) {
            return 0;
        }
        
        $totalTime = array_sum(array_column($compilations, 'duration'));
        return round($totalTime / count($compilations), 2);
    }
    
    private function calculateTemplateCacheEfficiency(array $blueprintData): float
    {
        $compiled = count($blueprintData['compilations'] ?? []);
        $cached = count($blueprintData['cache'] ?? []);
        $total = $compiled + $cached;
        
        return $total > 0 ? round(($cached / $total) * 100, 1) : 0;
    }
    
    private function analyzeServiceDependencies(array $services): array
    {
        // В реальной реализации здесь будет логика анализа зависимостей сервисов
        return [];
    }
    
    private function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }
        
        $value = (int) $limit;
        $unit = strtolower(substr($limit, -1));
        
        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
    
    private function initializeStorage(): void
    {
        // В реальной реализации здесь будет инициализация хранилища
    }
    
    private function startResponseTimeMeasurement(): void
    {
        // В реальной реализации здесь будет логика измерения времени ответа
    }
    
    private function startMemoryMeasurement(): void
    {
        // В реальной реализации здесь будет логика измерения памяти
    }
    
    private function startDatabaseMonitoring(): void
    {
        // В реальной реализации здесь будет логика мониторинга БД
    }
    
    private function startCacheMonitoring(): void
    {
        // В реальной реализации здесь будет логика мониторинга кэша
    }
    
    private function startTemplateMonitoring(): void
    {
        // В реальной реализации здесь будет логика мониторинга шаблонов
    }
    
    private function startServiceMonitoring(): void
    {
        // В реальной реализации здесь будет логика мониторинга сервисов
    }
}