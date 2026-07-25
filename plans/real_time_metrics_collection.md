# План сбора метрик производительности в реальном времени

## Обзор
Система для сбора, агрегации и анализа метрик производительности Architect Framework 2 в реальном времени.

## Архитектура сбора метрик

### 1. Компоненты системы

```
PerformanceMonitor (основной класс)
├── MetricCollector (сбор метрик)
├── MetricAggregator (агрегация данных)
├── MetricStorage (хранение метрик)
├── MetricAnalyzer (анализ данных)
└── MetricReporter (отчетность)
```

### 2. Типы собираемых метрик

#### 2.1. Метрики времени выполнения
- **Response Time**: Время от начала запроса до завершения ответа
- **Stage Timing**: Время выполнения каждого этапа (инициализация, маршрутизация, рендеринг и т.д.)
- **Component Timing**: Время работы отдельных компонентов (Container, Router, Blueprint и т.д.)

#### 2.2. Метрики использования памяти
- **Current Memory**: Текущее использование памяти
- **Peak Memory**: Пиковое использование памяти
- **Memory Limit**: Лимит памяти PHP
- **Memory Fragmentation**: Фрагментация памяти (если доступно)

#### 2.3. Метрики базы данных
- **Query Count**: Количество запросов к БД
- **Query Time**: Общее время выполнения запросов
- **Slow Queries**: Запросы, превышающие пороговое значение
- **Connection Count**: Количество соединений с БД

#### 2.4. Метрики кэширования
- **Cache Hits/Misses**: Попадания/промахи кэша
- **Cache Size**: Размер кэша
- **Cache Efficiency**: Эффективность кэширования (hit ratio)
- **Cache Operations**: Операции с кэшем (get, set, delete)

#### 2.5. Метрики шаблонов
- **Template Compilation**: Время компиляции шаблонов
- **Template Rendering**: Время рендеринга шаблонов
- **Template Cache**: Эффективность кэширования шаблонов
- **Template Count**: Количество используемых шаблонов

#### 2.6. Метрики сервисов
- **Service Loading**: Время загрузки сервисов
- **Service Count**: Количество загруженных сервисов
- **Service Dependencies**: Зависимости между сервисами
- **Service Initialization**: Время инициализации сервисов

## Реализация сбора метрик

### 1. PerformanceMonitor класс

```php
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
            'slow_queries' => $slowQueries,
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
        if ($metrics['database_queries']['count'] > ($this->thresholds['database_queries'] ?? 50)) {
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
}
```

### 2. MetricCollector класс

```php
<?php

declare(strict_types=1);

namespace Architect\Services\Performance\Metrics;

use Architect\Core\Container;

class MetricCollector
{
    private Container $container;
    private array $stageTimings = [];
    private array $databaseQueries = [];
    private array $cacheStats = [];
    private array $blueprintData = [];
    private array $serviceMetrics = [];
    private float $stageStartTime = 0;
    private string $currentStage = '';
    
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    public function start(): void
    {
        // Регистрация хуков для сбора метрик
        $this->registerDatabaseHooks();
        $this->registerCacheHooks();
        $this->registerBlueprintHooks();
        $this->registerServiceHooks();
    }
    
    public function startStage(string $stage): void
    {
        $this->currentStage = $stage;
        $this->stageStartTime = microtime(true);
    }
    
    public function endStage(): void
    {
        if ($this->currentStage && $this->stageStartTime > 0) {
            $duration = microtime(true) - $this->stageStartTime;
            $this->stageTimings[] = [
                'name' => $this->currentStage,
                'start' => $this->stageStartTime,
                'duration' => $duration,
                'memory_start' => memory_get_usage(),
                'memory_end' => memory_get_usage(),
            ];
            
            $this->currentStage = '';
            $this->stageStartTime = 0;
        }
    }
    
    public function recordDatabaseQuery(string $sql, float $duration, array $params = []): void
    {
        $this->databaseQueries[] = [
            'sql' => $sql,
            'duration' => $duration,
            'params' => $params,
            'timestamp' => microtime(true),
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
        ];
    }
    
    public function recordCacheOperation(string $operation, string $key, bool $hit = false): void
    {
        if (!isset($this->cacheStats[$operation])) {
            $this->cacheStats[$operation] = 0;
        }
        
        $this->cacheStats[$operation]++;
        
        if ($operation === 'get') {
            if ($hit) {
                $this->cacheStats['hits'] = ($this->cacheStats['hits'] ?? 0) + 1;
            } else {
                $this->cacheStats['misses'] = ($this->cacheStats['misses'] ?? 0) + 1;
            }
        }
    }
    
    public function getStageTimings(): array
    {
        return $this->stageTimings;
    }
    
    public function getDatabaseQueries(): array
    {
        return $this->databaseQueries;
    }
    
    public function getCacheStats(): array
    {
        return $this->cacheStats;
    }
    
    public function getBlueprintData(): array
    {
        return $this->blueprintData;
    }
    
    public function getServiceMetrics(): array
    {
        return $this->serviceMetrics;
    }
    
    public function getCacheSize(): int
    {
        // Реализация получения размера кэша
        return 0;
    }
    
    private function registerDatabaseHooks(): void
    {
        // Регистрация хуков для Axiom ORM
        if ($this->container->has('axiom.connection_manager')) {
            $connectionManager = $this->container->get('axiom.connection_manager');
            $connectionManager->setQueryCallback(function($sql, $duration, $params) {
                $this->recordDatabaseQuery($sql, $duration, $params);
            });
        }
    }
    
    private function registerCacheHooks(): void
    {
        // Регистрация хуков для CacheManager
        if ($this->container->has('cache.manager')) {
            // Патчинг методов кэша для отслеживания операций
        }
    }
    
    private function registerBlueprintHooks(): void
    {
        // Регистрация хуков для Blueprint
        if ($this->container->has('blueprint')) {
            $blueprint = $this->container->get('blueprint');
            $blueprint->setDebugCallback(function($event, $data) {
                $this->blueprintData[$event][] = $data;
            });
        }
    }
    
    private function registerServiceHooks(): void
    {
        // Регистрация хуков для Container
        $container = $this->container;
        $originalGet = new \ReflectionMethod($container, 'get');
        
        // Создание прокси-метода для отслеживания загрузки сервисов
    }
}
```

## Интеграция с существующими системами

### 1. Интеграция с Debug системой

```php
// architect/Services/Debug/Debug.php
class Debug extends AbstractService implements DebugInterface
{
    // Добавить в конструктор
    private ?PerformanceMonitorInterface $performanceMonitor = null;
    
    // Добавить в метод boot()
    public function boot(): void
    {
        // ... существующий код ...
        
        // Инициализация PerformanceMonitor
        if ($this->enabled && ($this->config['performance_monitoring'] ?? true)) {
            $this->initializePerformanceMonitor();
        }
    }
    
    private function initializePerformanceMonitor(): void
    {
        $config = $this->config['performance'] ?? [];
        $this->performanceMonitor = new PerformanceMonitor($this->container, $config);
        $this->performanceMonitor->start();
    }
    
    // Добавить в метод getData()
    public function getData(): array
    {
        // ... существующий код ...
        
        return [
            // ... существующие данные ...
            'performance_metrics' => $this->performanceMonitor?->collectMetrics() ?? [],
            'performance_monitor_enabled' => $this->performanceMonitor !== null,
            'performance_thresholds' => $this->config['performance']['thresholds'] ?? [],
        ];
    }
}
```

### 2. Конфигурация

```json
// app/config/debug.json
{
    "enabled": true,
    "performance_monitoring": true,
    "performance": {
        "enabled": true,
        "thresholds": {
            "response_time_ms": 500,
            "memory_mb": 128,
            "database_queries": 50,
            "slow_query_ms": 100,
            "template_compile_ms": 50,
            "service_load_ms": 20
        },
        "storage": {
            "type": "session", // session, file, database
            "max_entries": 100,
            "retention_days": 7
        },
        "alerts": {
            "enabled": true,
            "levels": ["warning", "critical"],
            "channels": ["console", "log"]
        }
    }
}
```

## Оптимизация производительности сбора метрик

### 1. Ленивая загрузка метрик
- Собирать только при необходимости (когда открыта вкладка Performance)
- Использовать кэширование агрегированных данных
- Отложенная инициализация тяжелых коллекторов

### 2. Минимизация накладных расходов
- Использовать lightweight хуки вместо тяжелого отслеживания
- Ограничить глубину backtrace для запросов к БД
- Использовать sampling для частых операций

### 3. Асинхронная обработка
- Сохранение метрик в фоновом режиме
- Асинхронная агрегация данных
- Отложенная генерация отчетов

## Тестирование

### 1. Unit тесты
- Тестирование сбора отдельных метрик
- Тестирование агрегации данных
- Тестирование анализа метрик

### 2. Интеграционные тесты
- Тестирование интеграции с Debug системой
- Тестирование работы с реальными компонентами
- Тестирование производительности сбора метрик

### 3. Нагрузочное тестирование
- Измерение накладных расходов
- Тестирование под нагрузкой
- Тестирование стабильности при длительной работе

## Заключение
Система сбора метрик производительности в реальном времени предоставит детальную информацию о работе Architect Framework 2, позволит выявлять узкие места и оптимизировать производительность, сокращая время загрузки страниц с 3 секунд до целевых 300-500 мс.