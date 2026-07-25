<?php

declare(strict_types=1);

/**
 * Performance Tab - Вкладка мониторинга производительности
 * Отображает метрики производительности, профилировщик, алерты и графики.
 */

?>
<script>
debugModules.performance = function() {
    const performanceData = debugData.performance || {};
    const profilerData = debugData.profiler || {};
    const hasPerformance = debugData.has_performance || false;
    
    if (!hasPerformance) {
        return '<div class="debug-no-data"><div style="color: #888; font-size: 10pt; text-align: center; padding: 40px;">Данные производительности не собраны. Убедитесь, что PerformanceMonitor включен.</div></div>';
    }
    
    // Основные метрики
    const metrics = performanceData.metrics || {};
    const stageTimings = performanceData.stage_timings || [];
    const databaseQueries = performanceData.database_queries || [];
    const cacheStats = performanceData.cache_stats || {};
    const alerts = performanceData.alerts || [];
    const profilerSegments = profilerData.segments || [];
    const profilerSlowest = profilerData.slowest || [];
    
    // Функции форматирования
    function formatTime(seconds) {
        if (seconds < 0.001) return (seconds * 1000000).toFixed(0) + ' µs';
        if (seconds < 1) return (seconds * 1000).toFixed(1) + ' ms';
        return seconds.toFixed(3) + ' s';
    }
    
    function formatMemory(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }
    
    function getSeverityColor(severity) {
        switch (severity) {
            case 'critical': return '#f44336';
            case 'high': return '#ff9800';
            case 'medium': return '#ffc107';
            case 'low': return '#4caf50';
            default: return '#9e9e9e';
        }
    }
    
    let html = '';
    
    // Статистика производительности
    html += '<div class="debug-performance-stats">';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;"><div style="color: #888; font-size: 8pt;">Общее время</div><div style="color: #64b5f6; font-size: 14pt; font-weight: 600;">' + formatTime(debugData.total_time) + '</div></div>';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;"><div style="color: #888; font-size: 8pt;">Пик памяти</div><div style="color: #ce93d8; font-size: 14pt; font-weight: 600;">' + formatMemory(debugData.memory_peak) + '</div></div>';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;"><div style="color: #888; font-size: 8pt;">Запросов БД</div><div style="color: #a5d6a7; font-size: 14pt; font-weight: 600;">' + databaseQueries.length + '</div></div>';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;"><div style="color: #888; font-size: 8pt;">Кэш попаданий</div><div style="color: ' + (cacheStats.hit_ratio > 80 ? '#4caf50' : '#ff9800') + '; font-size: 14pt; font-weight: 600;">' + (cacheStats.hit_ratio || 0) + '%</div></div>';
    html += '</div>';
    
    // Tabs
    html += '<div class="debug-tabs">';
    html += '<div class="debug-tab active" data-tab="performance-metrics">Метрики</div>';
    html += '<div class="debug-tab" data-tab="performance-profiler">Профилировщик</div>';
    html += '<div class="debug-tab" data-tab="performance-alerts">Алерты</div>';
    html += '<div class="debug-tab" data-tab="performance-charts">Графики</div>';
    html += '</div>';
    
    // Tab: Метрики
    html += '<div class="debug-tab-content active" id="tab-performance-metrics">';
    html += '<h3 style="color: #e5e7eb; margin-top: 0;">Этапы выполнения</h3>';
    if (stageTimings.length > 0) {
        html += '<table class="debug-table"><thead><tr><th>Этап</th><th>Время</th><th>Память</th><th>Запросы</th></tr></thead><tbody>';
        stageTimings.forEach(stage => {
            html += '<tr><td style="color: #e5e7eb;">' + stage.name + '</td><td style="color: #64b5f6;">' + formatTime(stage.duration) + '</td><td style="color: #ce93d8;">' + formatMemory(stage.memory) + '</td><td style="color: #a5d6a7;">' + (stage.queries || 0) + '</td></tr>';
        });
        html += '</tbody></table>';
    } else {
        html += '<div style="color: #888; font-size: 10pt; text-align: center; padding: 20px;">Нет данных по этапам</div>';
    }
    
    html += '<h3 style="color: #e5e7eb; margin-top: 20px;">Статистика кэша</h3>';
    if (cacheStats.hits !== undefined) {
        html += '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px;">';
        html += '<div style="background: #252525; padding: 10px; border-radius: 4px;"><div style="color: #888; font-size: 8pt;">Попадания</div><div style="color: #4caf50; font-size: 12pt;">' + (cacheStats.hits || 0) + '</div></div>';
        html += '<div style="background: #252525; padding: 10px; border-radius: 4px;"><div style="color: #888; font-size: 8pt;">Промахи</div><div style="color: #f44336; font-size: 12pt;">' + (cacheStats.misses || 0) + '</div></div>';
        html += '<div style="background: #252525; padding: 10px; border-radius: 4px;"><div style="color: #888; font-size: 8pt;">Эффективность</div><div style="color: ' + (cacheStats.hit_ratio > 80 ? '#4caf50' : '#ff9800') + '; font-size: 12pt;">' + (cacheStats.hit_ratio || 0) + '%</div></div>';
        html += '</div>';
    } else {
        html += '<div style="color: #888; font-size: 10pt; text-align: center; padding: 20px;">Нет данных по кэшу</div>';
    }
    html += '</div>';
    
    // Tab: Профилировщик
    html += '<div class="debug-tab-content" id="tab-performance-profiler">';
    if (profilerSegments.length > 0) {
        html += '<h3 style="color: #e5e7eb; margin-top: 0;">Измеренные сегменты</h3>';
        html += '<table class="debug-table"><thead><tr><th>Название</th><th>Время</th><th>Память</th><th>Вызовы</th></tr></thead><tbody>';
        profilerSegments.forEach(segment => {
            html += '<tr><td style="color: #e5e7eb;">' + segment.name + '</td><td style="color: #64b5f6;">' + formatTime(segment.duration) + '</td><td style="color: #ce93d8;">' + formatMemory(segment.memory) + '</td><td style="color: #a5d6a7;">' + (segment.calls || 1) + '</td></tr>';
        });
        html += '</tbody></table>';
        
        if (profilerSlowest.length > 0) {
            html += '<h3 style="color: #e5e7eb; margin-top: 20px;">Самые медленные сегменты</h3>';
            html += '<table class="debug-table"><thead><tr><th>Название</th><th>Время</th><th>Процент</th></tr></thead><tbody>';
            profilerSlowest.forEach(segment => {
                const percent = segment.percent ? segment.percent.toFixed(1) + '%' : '';
                html += '<tr><td style="color: #e5e7eb;">' + segment.name + '</td><td style="color: #f44336; font-weight: 500;">' + formatTime(segment.duration) + '</td><td style="color: #ff9800;">' + percent + '</td></tr>';
            });
            html += '</tbody></table>';
        }
    } else {
        html += '<div style="color: #888; font-size: 10pt; text-align: center; padding: 40px;">Профилировщик не собрал данные. Используйте Profiler::start() и Profiler::stop() для измерения.</div>';
    }
    html += '</div>';
    
    // Tab: Алерты
    html += '<div class="debug-tab-content" id="tab-performance-alerts">';
    if (alerts.length > 0) {
        html += '<h3 style="color: #e5e7eb; margin-top: 0;">Активные алерты производительности</h3>';
        html += '<table class="debug-table"><thead><tr><th>Уровень</th><th>Метрика</th><th>Значение</th><th>Порог</th><th>Время</th></tr></thead><tbody>';
        alerts.forEach(alert => {
            const color = getSeverityColor(alert.severity);
            html += '<tr style="background: ' + color + '10;">';
            html += '<td><span style="color: ' + color + '; font-weight: 500;">' + alert.severity + '</span></td>';
            html += '<td style="color: #e5e7eb;">' + alert.metric + '</td>';
            html += '<td style="color: #fff; font-weight: 500;">' + alert.value + '</td>';
            html += '<td style="color: #ff9800;">' + alert.threshold + '</td>';
            html += '<td style="color: #9ca3af;">' + new Date(alert.timestamp * 1000).toLocaleTimeString() + '</td>';
            html += '</tr>';
        });
        html += '</tbody></table>';
    } else {
        html += '<div style="color: #888; font-size: 10pt; text-align: center; padding: 40px;">Нет активных алертов. Все метрики в пределах нормы.</div>';
    }
    html += '</div>';
    
    // Tab: Графики
    html += '<div class="debug-tab-content" id="tab-performance-charts">';
    html += '<h3 style="color: #e5e7eb; margin-top: 0;">Визуализация производительности</h3>';
    html += '<div style="color: #888; font-size: 10pt; margin-bottom: 20px;">Графики будут отображаться при наличии исторических данных.</div>';
    
    // Простой график на CSS
    if (stageTimings.length > 0) {
        const maxDuration = Math.max(...stageTimings.map(s => s.duration));
        html += '<div style="margin-top: 30px;"><div style="color: #e5e7eb; margin-bottom: 10px;">Распределение времени по этапам:</div>';
        html += '<div style="background: #1a1a1a; border-radius: 4px; padding: 15px;">';
        stageTimings.forEach(stage => {
            const width = maxDuration > 0 ? (stage.duration / maxDuration * 100) : 0;
            html += '<div style="margin-bottom: 8px;">';
            html += '<div style="color: #e5e7eb; font-size: 9pt; margin-bottom: 2px;">' + stage.name + ' <span style="color: #64b5f6;">' + formatTime(stage.duration) + '</span></div>';
            html += '<div style="background: #333; height: 12px; border-radius: 6px; overflow: hidden;">';
            html += '<div style="background: linear-gradient(90deg, #64b5f6, #2196f3); height: 100%; width: ' + width + '%;"></div>';
            html += '</div></div>';
        });
        html += '</div></div>';
    }
    
    html += '</div>';
    
    return html;
};
</script>