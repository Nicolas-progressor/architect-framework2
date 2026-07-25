<?php

declare(strict_types=1);

namespace Architect\Services\Performance\Metrics;

use Architect\Core\Container;
use Architect\Services\Performance\Contracts\MetricCollectorInterface;

class MetricCollector implements MetricCollectorInterface
{
    private Container $container;
    private array $stageTimings = [];
    private array $databaseQueries = [];
    private array $cacheStats = ['hits' => 0, 'misses' => 0, 'operations' => []];
    private array $blueprintData = ['templates' => [], 'compilations' => [], 'cache' => []];
    private array $serviceMetrics = [];
    private array $memoryUsage = [];
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
        $this->registerMemoryHooks();
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
        $this->cacheStats['operations'][] = [
            'operation' => $operation,
            'key' => $key,
            'hit' => $hit,
            'timestamp' => microtime(true),
        ];
        
        if ($operation === 'get') {
            if ($hit) {
                $this->cacheStats['hits'] = ($this->cacheStats['hits'] ?? 0) + 1;
            } else {
                $this->cacheStats['misses'] = ($this->cacheStats['misses'] ?? 0) + 1;
            }
        }
    }
    
    public function recordTemplateCompilation(string $template, float $duration): void
    {
        $this->blueprintData['compilations'][] = [
            'template' => $template,
            'duration' => $duration,
            'timestamp' => microtime(true),
        ];
    }
    
    public function recordServiceLoading(string $service, float $duration): void
    {
        $this->serviceMetrics[] = [
            'service' => $service,
            'duration' => $duration,
            'timestamp' => microtime(true),
        ];
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
    
    public function getMemoryUsage(): array
    {
        return $this->memoryUsage;
    }
    
    public function getCacheSize(): int
    {
        // В реальной реализации здесь будет логика получения размера кэша
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
            // В реальной реализации здесь будет патчинг методов кэша
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
        // В реальной реализации здесь будет логика отслеживания загрузки сервисов
    }
    
    private function registerMemoryHooks(): void
    {
        // Регистрация хуков для отслеживания использования памяти
        // В реальной реализации здесь будет логика отслеживания памяти
    }
}