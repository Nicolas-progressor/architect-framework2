# План интеграции PerformanceMonitor в Debug систему

## Обзор
Интеграция системы мониторинга производительности в существующее дебаг-меню Architect Framework 2.

## Текущая архитектура
1. **Debug система** (`architect/Services/Debug/Debug.php`)
   - Собирает данные о времени выполнения, памяти, запросах, кэше, сессиях
   - Рендерит панель через `View/Panel.php`
   - Использует вкладки в `View/tabs/`
   - Передает данные через `debugData` в JavaScript

2. **PerformanceMonitor** (планируемая система)
   - Собирает метрики производительности в реальном времени
   - Анализирует узкие места
   - Предоставляет рекомендации по оптимизации

## Архитектура интеграции

### 1. Расширение класса Debug
```php
// architect/Services/Debug/Debug.php
class Debug extends AbstractService implements DebugInterface
{
    // Добавить новые свойства
    private ?PerformanceMonitorInterface $performanceMonitor = null;
    private array $performanceMetrics = [];
    
    // В методе boot() инициализировать PerformanceMonitor
    public function boot(): void
    {
        // ... существующий код ...
        
        // Инициализация PerformanceMonitor если включен
        if ($this->enabled && ($this->config['performance_monitoring'] ?? true)) {
            $this->initializePerformanceMonitor();
        }
    }
    
    private function initializePerformanceMonitor(): void
    {
        $this->performanceMonitor = new PerformanceMonitor();
        $this->performanceMonitor->start();
    }
    
    // Добавить метод для получения метрик производительности
    public function getPerformanceMetrics(): array
    {
        if ($this->performanceMonitor === null) {
            return [];
        }
        
        return $this->performanceMonitor->collectMetrics();
    }
    
    // Обновить метод getData() для включения метрик производительности
    public function getData(): array
    {
        // ... существующий код ...
        
        return [
            // ... существующие данные ...
            'performance_metrics' => $this->getPerformanceMetrics(),
            'performance_monitor_enabled' => $this->performanceMonitor !== null,
            'performance_thresholds' => $this->getPerformanceThresholds(),
        ];
    }
}
```

### 2. Создание PerformanceMonitor
```php
// architect/Services/Performance/PerformanceMonitor.php
class PerformanceMonitor implements PerformanceMonitorInterface
{
    private array $metrics = [];
    private array $thresholds = [];
    private float $startTime;
    
    public function start(): void
    {
        $this->startTime = microtime(true);
        $this->initializeMetrics();
    }
    
    public function collectMetrics(): array
    {
        return [
            'response_time' => $this->measureResponseTime(),
            'memory_usage' => $this->measureMemoryUsage(),
            'database_queries' => $this->collectDatabaseMetrics(),
            'template_rendering' => $this->collectTemplateMetrics(),
            'service_loading' => $this->collectServiceMetrics(),
            'cache_efficiency' => $this->collectCacheMetrics(),
            'recommendations' => $this->generateRecommendations(),
        ];
    }
}
```

### 3. Интеграция с существующими системами
- **Database**: Подключение к Query Builder для сбора метрик запросов
- **Cache**: Интеграция с CacheManager для анализа эффективности кэширования
- **Blueprint**: Мониторинг времени компиляции и рендеринга шаблонов
- **Container**: Отслеживание времени инициализации сервисов

## Вкладка Performance в дебаг-меню

### Структура вкладки
1. **PerformanceTab.php** - основная вкладка с метриками
2. **PerformanceCharts.js** - JavaScript для визуализации графиков
3. **PerformanceAlerts.js** - система алертов

### Основные разделы вкладки

#### 1. Обзор производительности
- Общее время выполнения
- Использование памяти
- Количество запросов к БД
- Эффективность кэширования

#### 2. Детальный анализ
- Временная шкала выполнения
- График использования памяти
- Анализ запросов к БД
- Статистика шаблонов

#### 3. Рекомендации по оптимизации
- Выявленные узкие места
- Конкретные рекомендации
- Оценка потенциального улучшения

#### 4. Мониторинг в реальном времени
- Live графики
- Обновление метрик
- История производительности

## Конфигурация

### debug.json
```json
{
    "enabled": true,
    "performance_monitoring": true,
    "performance_thresholds": {
        "response_time_ms": 500,
        "memory_mb": 128,
        "database_queries": 50,
        "template_compile_ms": 100
    },
    "performance_alerts": {
        "enabled": true,
        "notify_in_console": true,
        "log_to_file": false
    }
}
```

## Этапы реализации

### Этап 1: Базовая интеграция
1. Создать класс PerformanceMonitor
2. Интегрировать в Debug систему
3. Добавить базовые метрики

### Этап 2: Вкладка Performance
1. Создать PerformanceTab.php
2. Добавить в список вкладок в Panel.php
3. Реализовать базовую визуализацию

### Этап 3: Расширенные возможности
1. Реализовать графики и диаграммы
2. Добавить систему алертов
3. Реализовать экспорт метрик

### Этап 4: Оптимизация
1. Минимизировать накладные расходы
2. Добавить кэширование метрик
3. Оптимизировать сбор данных

## Технические требования

### Производительность
- Нагрузка от мониторинга < 1% от общего времени выполнения
- Использование памяти < 5MB
- Автоматическое отключение в production

### Совместимость
- PHP 8.1+
- Совместимость с существующим API Debug системы
- Поддержка всех драйверов кэша
- Интеграция с Axiom ORM

### Безопасность
- Ограничение доступа по IP (если настроено)
- Маскирование чувствительных данных
- Защита от XSS в визуализации

## Тестирование

### Unit тесты
- Тестирование сбора метрик
- Тестирование пороговых значений
- Тестирование интеграции с Debug

### Интеграционные тесты
- Тестирование в различных окружениях
- Тестирование с разными конфигурациями
- Тестирование производительности

### Браузерные тесты
- Тестирование рендеринга вкладки
- Тестирование интерактивных элементов
- Тестирование обновления в реальном времени

## Документация
1. Руководство по использованию
2. API документация
3. Примеры конфигурации
4. Руководство по устранению неполадок

## Оценка времени реализации
- Этап 1: 2-3 дня
- Этап 2: 3-4 дня  
- Этап 3: 4-5 дней
- Этап 4: 2-3 дня
- Тестирование: 2-3 дня

**Итого: 13-18 дней разработки**

## Риски и митигация
1. **Производительность**: Реализовать ленивую загрузку метрик
2. **Совместимость**: Тестировать со всеми компонентами фреймворка
3. **Безопасность**: Реализовать строгую валидацию входных данных
4. **Сложность**: Разбить на мелкие, тестируемые модули

## Заключение
Интеграция PerformanceMonitor в Debug систему предоставит разработчикам мощные инструменты для анализа и оптимизации производительности Architect Framework 2, что позволит сократить время загрузки страниц с 3 секунд до целевых 300-500 мс.