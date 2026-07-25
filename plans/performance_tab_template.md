# Шаблон вкладки Performance для дебаг-меню

## Файловая структура
```
architect/Services/Debug/View/tabs/PerformanceTab.php
architect/Services/Debug/View/tabs/performance/
├── PerformanceCharts.js
├── PerformanceAlerts.js
├── PerformanceMetrics.js
└── PerformanceVisualization.js
```

## PerformanceTab.php - основной файл вкладки

```php
<?php

declare(strict_types=1);

/**
 * Performance Tab - Вкладка мониторинга производительности
 * Отображает метрики производительности, графики и рекомендации
 */

?>
<script>
debugModules.performance = function() {
    const metrics = debugData.performance_metrics || {};
    const thresholds = debugData.performance_thresholds || {};
    const enabled = debugData.performance_monitor_enabled || false;
    
    if (!enabled) {
        return '<div class="debug-warning">Performance monitoring is disabled. Enable it in debug.json configuration.</div>';
    }
    
    // Основные метрики
    const responseTime = metrics.response_time || {};
    const memoryUsage = metrics.memory_usage || {};
    const databaseMetrics = metrics.database_queries || {};
    const templateMetrics = metrics.template_rendering || {};
    const serviceMetrics = metrics.service_loading || {};
    const cacheMetrics = metrics.cache_efficiency || {};
    const recommendations = metrics.recommendations || [];
    
    let html = '';
    
    // Заголовок и статус
    html += '<div class="debug-performance-header">';
    html += '<h3 style="margin: 0; color: #e5e7eb;">Performance Monitor</h3>';
    html += '<div class="debug-performance-status">';
    
    // Статус производительности
    const overallStatus = calculateOverallStatus(metrics, thresholds);
    html += '<span class="debug-performance-badge ' + overallStatus.class + '">' + overallStatus.text + '</span>';
    html += '</div></div>';
    
    // Tabs
    html += '<div class="debug-tabs">';
    html += '<div class="debug-tab active" data-tab="performance-overview">Обзор</div>';
    html += '<div class="debug-tab" data-tab="performance-detailed">Детальный анализ</div>';
    html += '<div class="debug-tab" data-tab="performance-charts">Графики</div>';
    html += '<div class="debug-tab" data-tab="performance-recommendations">Рекомендации</div>';
    html += '<div class="debug-tab" data-tab="performance-history">История</div>';
    html += '</div>';
    
    // Tab: Обзор
    html += '<div class="debug-tab-content active" id="tab-performance-overview">';
    html += renderOverviewTab(metrics, thresholds);
    html += '</div>';
    
    // Tab: Детальный анализ
    html += '<div class="debug-tab-content" id="tab-performance-detailed">';
    html += renderDetailedTab(metrics, thresholds);
    html += '</div>';
    
    // Tab: Графики
    html += '<div class="debug-tab-content" id="tab-performance-charts">';
    html += '<div id="performance-charts-container"></div>';
    html += '</div>';
    
    // Tab: Рекомендации
    html += '<div class="debug-tab-content" id="tab-performance-recommendations">';
    html += renderRecommendationsTab(recommendations);
    html += '</div>';
    
    // Tab: История
    html += '<div class="debug-tab-content" id="tab-performance-history">';
    html += '<div id="performance-history-container"></div>';
    html += '</div>';
    
    // Инициализация графиков
    html += '<script>';
    html += 'setTimeout(function() { initializePerformanceCharts(metrics); }, 100);';
    html += '</script>';
    
    return html;
    
    // Вспомогательные функции
    function calculateOverallStatus(metrics, thresholds) {
        const issues = [];
        
        if (metrics.response_time?.current > thresholds.response_time_ms) {
            issues.push('response_time');
        }
        
        if (metrics.memory_usage?.peak_mb > thresholds.memory_mb) {
            issues.push('memory_usage');
        }
        
        if (metrics.database_queries?.count > thresholds.database_queries) {
            issues.push('database_queries');
        }
        
        if (issues.length === 0) {
            return { class: 'status-ok', text: 'OK' };
        } else if (issues.length <= 2) {
            return { class: 'status-warning', text: 'WARNING (' + issues.length + ' issues)' };
        } else {
            return { class: 'status-critical', text: 'CRITICAL (' + issues.length + ' issues)' };
        }
    }
    
    function renderOverviewTab(metrics, thresholds) {
        let html = '<div class="debug-performance-overview">';
        
        // Карточки с основными метриками
        html += '<div class="debug-performance-cards">';
        
        // Response Time
        const responseTimeColor = getMetricColor(
            metrics.response_time?.current || 0,
            thresholds.response_time_ms,
            thresholds.response_time_ms * 0.7
        );
        html += '<div class="debug-performance-card">';
        html += '<div class="card-title">Response Time</div>';
        html += '<div class="card-value" style="color: ' + responseTimeColor + ';">' + 
                (metrics.response_time?.current || 0).toFixed(1) + ' ms</div>';
        html += '<div class="card-threshold">Threshold: ' + thresholds.response_time_ms + ' ms</div>';
        html += '</div>';
        
        // Memory Usage
        const memoryColor = getMetricColor(
            metrics.memory_usage?.peak_mb || 0,
            thresholds.memory_mb,
            thresholds.memory_mb * 0.7
        );
        html += '<div class="debug-performance-card">';
        html += '<div class="card-title">Memory Usage</div>';
        html += '<div class="card-value" style="color: ' + memoryColor + ';">' + 
                (metrics.memory_usage?.peak_mb || 0).toFixed(1) + ' MB</div>';
        html += '<div class="card-threshold">Threshold: ' + thresholds.memory_mb + ' MB</div>';
        html += '</div>';
        
        // Database Queries
        const dbColor = getMetricColor(
            metrics.database_queries?.count || 0,
            thresholds.database_queries,
            thresholds.database_queries * 0.7
        );
        html += '<div class="debug-performance-card">';
        html += '<div class="card-title">Database Queries</div>';
        html += '<div class="card-value" style="color: ' + dbColor + ';">' + 
                (metrics.database_queries?.count || 0) + '</div>';
        html += '<div class="card-threshold">Threshold: ' + thresholds.database_queries + '</div>';
        html += '</div>';
        
        // Cache Hit Ratio
        const cacheRatio = metrics.cache_efficiency?.hit_ratio || 0;
        const cacheColor = getCacheColor(cacheRatio);
        html += '<div class="debug-performance-card">';
        html += '<div class="card-title">Cache Hit Ratio</div>';
        html += '<div class="card-value" style="color: ' + cacheColor + ';">' + 
                cacheRatio.toFixed(1) + '%</div>';
        html += '<div class="card-threshold">Target: > 80%</div>';
        html += '</div>';
        
        html += '</div>'; // .debug-performance-cards
        
        // Быстрый анализ
        html += '<div class="debug-performance-quick-analysis">';
        html += '<h4>Quick Analysis</h4>';
        html += '<ul>';
        
        if (metrics.response_time?.current > thresholds.response_time_ms) {
            html += '<li class="analysis-warning">Response time exceeds threshold</li>';
        }
        
        if (metrics.memory_usage?.peak_mb > thresholds.memory_mb) {
            html += '<li class="analysis-warning">Memory usage exceeds threshold</li>';
        }
        
        if (metrics.database_queries?.count > thresholds.database_queries) {
            html += '<li class="analysis-warning">Too many database queries</li>';
        }
        
        if (metrics.cache_efficiency?.hit_ratio < 80) {
            html += '<li class="analysis-warning">Low cache hit ratio</li>';
        }
        
        if (html.indexOf('analysis-warning') === -1) {
            html += '<li class="analysis-ok">All metrics within acceptable ranges</li>';
        }
        
        html += '</ul>';
        html += '</div>';
        
        html += '</div>'; // .debug-performance-overview
        
        return html;
    }
    
    function renderDetailedTab(metrics, thresholds) {
        let html = '<div class="debug-performance-detailed">';
        
        // Response Time Details
        html += '<div class="performance-section">';
        html += '<h4>Response Time Analysis</h4>';
        html += '<table class="debug-table">';
        html += '<tr><td>Current</td><td>' + (metrics.response_time?.current || 0).toFixed(1) + ' ms</td></tr>';
        html += '<tr><td>Average</td><td>' + (metrics.response_time?.average || 0).toFixed(1) + ' ms</td></tr>';
        html += '<tr><td>Peak</td><td>' + (metrics.response_time?.peak || 0).toFixed(1) + ' ms</td></tr>';
        html += '<tr><td>Threshold</td><td>' + thresholds.response_time_ms + ' ms</td></tr>';
        html += '</table>';
        html += '</div>';
        
        // Memory Usage Details
        html += '<div class="performance-section">';
        html += '<h4>Memory Usage Analysis</h4>';
        html += '<table class="debug-table">';
        html += '<tr><td>Current</td><td>' + (metrics.memory_usage?.current_mb || 0).toFixed(1) + ' MB</td></tr>';
        html += '<tr><td>Peak</td><td>' + (metrics.memory_usage?.peak_mb || 0).toFixed(1) + ' MB</td></tr>';
        html += '<tr><td>Limit</td><td>' + (metrics.memory_usage?.limit_mb || 0).toFixed(1) + ' MB</td></tr>';
        html += '<tr><td>Threshold</td><td>' + thresholds.memory_mb + ' MB</td></tr>';
        html += '</table>';
        html += '</div>';
        
        // Database Analysis
        html += '<div class="performance-section">';
        html += '<h4>Database Analysis</h4>';
        html += '<table class="debug-table">';
        html += '<tr><td>Total Queries</td><td>' + (metrics.database_queries?.count || 0) + '</td></tr>';
        html += '<tr><td>Slow Queries</td><td>' + (metrics.database_queries?.slow_count || 0) + '</td></tr>';
        html += '<tr><td>Total Time</td><td>' + (metrics.database_queries?.total_time || 0).toFixed(1) + ' ms</td></tr>';
        html += '<tr><td>Average Time</td><td>' + (metrics.database_queries?.average_time || 0).toFixed(1) + ' ms</td></tr>';
        html += '</table>';
        html += '</div>';
        
        // Template Analysis
        html += '<div class="performance-section">';
        html += '<h4>Template Analysis</h4>';
        html += '<table class="debug-table">';
        html += '<tr><td>Total Templates</td><td>' + (metrics.template_rendering?.count || 0) + '</td></tr>';
        html += '<tr><td>Compiled</td><td>' + (metrics.template_rendering?.compiled || 0) + '</td></tr>';
        html += '<tr><td>Cached</td><td>' + (metrics.template_rendering?.cached || 0) + '</td></tr>';
        html += '<tr><td>Total Time</td><td>' + (metrics.template_rendering?.total_time || 0).toFixed(1) + ' ms</td></tr>';
        html += '</table>';
        html += '</div>';
        
        html += '</div>'; // .debug-performance-detailed
        
        return html;
    }
    
    function renderRecommendationsTab(recommendations) {
        let html = '<div class="debug-performance-recommendations">';
        
        if (recommendations.length === 0) {
            html += '<div class="no-recommendations">No optimization recommendations at this time.</div>';
        } else {
            html += '<div class="recommendations-list">';
            
            recommendations.forEach((rec, index) => {
                const priorityClass = 'priority-' + (rec.priority || 'medium');
                html += '<div class="recommendation-item ' + priorityClass + '">';
                html += '<div class="recommendation-header">';
                html += '<span class="recommendation-priority">' + (rec.priority || 'MEDIUM').toUpperCase() + '</span>';
                html += '<span class="recommendation-title">' + rec.title + '</span>';
                html += '</div>';
                html += '<div class="recommendation-description">' + rec.description + '</div>';
                
                if (rec.impact) {
                    html += '<div class="recommendation-impact">';
                    html += '<strong>Potential Impact:</strong> ' + rec.impact;
                    html += '</div>';
                }
                
                if (rec.solution) {
                    html += '<div class="recommendation-solution">';
                    html += '<strong>Solution:</strong> ' + rec.solution;
                    html += '</div>';
                }
                
                html += '</div>';
            });
            
            html += '</div>';
        }
        
        html += '</div>';
        
        return html;
    }
    
    function getMetricColor(value, threshold, warningThreshold) {
        if (value > threshold) {
            return '#f44336'; // Красный - превышение
        } else if (value > warningThreshold) {
            return '#ff9800'; // Оранжевый - предупреждение
        } else {
            return '#4caf50'; // Зеленый - норма
        }
    }
    
    function getCacheColor(ratio) {
        if (ratio >= 80) {
            return '#4caf50'; // Зеленый
        } else if (ratio >= 60) {
            return '#ff9800'; // Оранжевый
        } else {
            return '#f44336'; // Красный
        }
    }
};
</script>
```

## Интеграция с Panel.php

### 1. Добавление в список вкладок
```php
// В architect/Services/Debug/View/Panel.php
$tabFiles = [
    'TimeTab.php',
    'MemoryTab.php', 
    'DatabaseTab.php',
    'LogsTab.php',
    'CacheTab.php',
    'SessionTab.php',
    'EnvironmentTab.php',
    'RoutingTab.php',
    'DebugTab.php',
    'BlueprintTab.php',
    'PerformanceTab.php', // Новая вкладка
];
```

### 2. Добавление в debugModules
```javascript
// В Panel.php, в секции JavaScript
const modulesWithTabs = ['debug', 'routing', 'blueprint', 'time', 'database', 'logs', 'cache', 'session', 'memory', 'performance'];
```

## CSS стили для вкладки Performance

Добавить в `architect/Services/Debug/View/partials/Styles.php`:

```css
/* Performance Tab Styles */
.debug-performance-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #333;
}

.debug-performance-status {
    display: flex;
    align-items: center;
}

.debug-performance-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.debug-performance-badge.status-ok {
    background: #2e7d32;
    color: #fff;
}

.debug-performance-badge.status-warning {
    background: #f57c00;
    color: #fff;
}

.debug-performance-badge.status-critical {
    background: #c62828;
    color: #fff;
}

.debug-performance-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}

.debug-performance-card {
    background: #252525;
    border-radius: 6px;
    padding: 12px;
    border-left: 4px solid #444;
}

.debug-performance-card:hover {
    background: #2a2a2a;
}

.debug-performance-card .card-title {
    color: #888;
    font-size: 11px;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.debug-performance-card .card-value {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 5px;
}

.debug-performance-card .card-threshold {
    color: #666;
    font-size: 10px;
}

.debug-performance-quick-analysis {
    background: #1a1a1a;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 20px;
}

.debug-performance-quick-analysis h4 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #e5e7eb;
}

.debug-performance-quick-analysis ul {
    margin: 0;
    padding-left: 20px;
}

.debug-performance-quick-analysis li {
    margin-bottom: 5px;
    color: #ccc;
}

.debug-performance-quick-analysis .analysis-ok {
    color: #4caf50;
}

.debug-performance-quick-analysis .analysis-warning {
    color: #ff9800;
}

.performance-section {
    background: #1a1a1a;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 15px;
}

.performance-section h4 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #e5e7eb;
}

.debug-performance-recommendations {
    background: #1a1a1a;
    border-radius: 6px;
    padding: 15px;
}

.recommendations-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.recommendation-item {
    background: #252525;
    border-radius: 6px;
    padding: 12px;
    border-left: 4px solid #444;
}

.recommendation-item.priority-high {
    border-left-color: #f44336;
}

.recommendation-item.priority-medium {
    border-left-color: #ff9800;
}

.recommendation-item.priority-low {
    border-left-color: #4caf50;
}

.recommendation-header {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.recommendation-priority {
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
    font-weight: 600;
    margin-right: 8px;
    text-transform: uppercase;
}

.recommendation-item.priority-high .recommendation-priority {
    background: #f44336;
    color: #fff;
}

.recommendation-item.priority-medium .recommendation-priority {
    background: #ff9800;
    color: #fff;
}

.recommendation-item.priority-low .recommendation-priority {
    background: #4caf50;
    color: #fff;
}

.recommendation-title {
    font-weight: 600;
    color: #e5e7eb;
}

.recommendation-description {
    color: #ccc;
    margin-bottom: 8px;
    font-size: 13px;
}

.recommendation-impact,
.recommendation-solution {
    color: #888;
    font-size: 12px;
    margin-top: 5px;
}

.recommendation-impact strong,
.recommendation-solution strong {
    color: #aaa;
}

.no-recommendations {
    color: #888;
    text-align: center;
    padding: 20px;
    font-style: italic;
}
```

## JavaScript файлы для расширенной функциональности

### PerformanceCharts.js
```javascript
function initializePerformanceCharts(metrics) {
    const container = document.getElementById('performance-charts-container');
    if (!container) return;
    
    // Response Time Chart
    const responseTimeChart = createLineChart('Response Time (ms)', [
        { label: 'Current', value: metrics.response_time?.current || 0 },
        { label: 'Average', value: metrics.response_time?.average || 0 },
        { label: 'Peak', value: metrics.response_time?.peak || 0 }
    ]);
    
    // Memory Usage Chart
    const memoryChart = createBarChart('Memory Usage (MB)', [
        { label: 'Current', value: metrics.memory_usage?.current_mb || 0 },
        { label: 'Peak', value: metrics.memory_usage?.peak_mb || 0 },
        { label: 'Limit', value: metrics.memory_usage?.limit_mb || 0 }
    ]);
    
    // Database Queries Chart
    const dbChart = createPieChart('Database Queries', [
        { label: 'Normal', value: (metrics.database_queries?.count || 0) - (metrics.database_queries?.slow_count || 0) },
        { label: 'Slow', value: metrics.database_queries?.slow_count || 0 }
    ]);
    
    container.innerHTML = responseTimeChart + memoryChart + dbChart;
}
```

## Конфигурация в debug.json

```json
{
    "enabled": true,
    "performance_monitoring": true,
    "performance_thresholds": {
        "response_time_ms": 500,
        "memory_mb": 128,
        "database_queries": 50,
        "template_compile_ms": 100,
        "service_load_ms": 50
    },
    "performance_alerts": {
        "enabled": true,
        "notify_in_console": true,
        "log_to_file": false,
        "alert_levels": ["warning", "critical"]
    },
    "performance_history": {
        "enabled": true,
        "max_entries": 100,
        "storage": "session" // session, localstorage, or server
    }
}
```

## Тестирование вкладки

### 1. Функциональное тестирование
- Проверка отображения метрик
- Проверка работы вкладок
- Проверка цветовой индикации
- Проверка рекомендаций

### 2. Производительность
- Время загрузки вкладки < 100ms
- Использование памяти < 10MB
- Совместимость с различными браузерами

### 3. Интеграционное тестирование
- Совместимость с существующим дебаг-меню
- Корректная работа с другими вкладками
- Обработка отсутствующих данных

## Заключение
Вкладка Performance предоставит разработчикам мощный инструмент для мониторинга и оптимизации производительности Architect Framework 2, интегрированный в существующую систему отладки.