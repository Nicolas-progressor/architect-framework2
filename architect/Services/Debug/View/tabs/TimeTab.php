<?php

declare(strict_types=1);

/**
 * Time Tab - Вкладка времени выполнения
 */

?>
<script>
debugModules.time = function() {
    const stages = debugData.stages;
    const stagesArray = Object.entries(stages).map(([name, stage]) => ({
        name,
        start: stage.start,
        duration: stage.duration,
        startMs: stage.start * 1000,
        durationMs: stage.duration * 1000
    }));
    
    // Общее время выполнения
    const totalDuration = Math.max(...stagesArray.map(s => s.start + s.duration));
    const totalDurationMs = totalDuration * 1000;
    
    // Сортировка по длительности
    const sortedByDuration = [...stagesArray].sort((a, b) => b.durationMs - a.durationMs);
    
    // Метрики
    const durations = stagesArray.map(s => s.durationMs).filter(d => d > 0);
    const avgDuration = durations.length > 0 ? durations.reduce((a, b) => a + b, 0) / durations.length : 0;
    const sortedDurations = [...durations].sort((a, b) => a - b);
    const medianDuration = sortedDurations.length > 0 
        ? (sortedDurations.length % 2 === 0 
            ? (sortedDurations[sortedDurations.length / 2 - 1] + sortedDurations[sortedDurations.length / 2]) / 2 
            : sortedDurations[Math.floor(sortedDurations.length / 2)]) 
        : 0;
    
    // Функции форматирования
    function getDurationColor(duration, total) {
        const percent = (duration / total) * 100;
        if (percent < 5) return '#4caf50';
        if (percent < 20) return '#8bc34a';
        if (percent < 40) return '#ffc107';
        if (percent < 60) return '#ff9800';
        return '#f44336';
    }
    
    function formatPercent(duration, total) {
        return ((duration / total) * 100).toFixed(1) + '%';
    }
    
    let html = '';
    
    // Статистика
    html += '<div class="debug-time-stats">';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;"><div style="color: #888; font-size: 8pt;">Всего этапов</div><div style="color: #64b5f6; font-size: 14pt; font-weight: 600;">' + stagesArray.length + '</div></div>';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;"><div style="color: #888; font-size: 8pt;">Общее время</div><div style="color: #e5e7eb; font-size: 14pt; font-weight: 600;">' + totalDurationMs.toFixed(1) + ' ms</div></div>';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;"><div style="color: #888; font-size: 8pt;">Среднее время</div><div style="color: #ce93d8; font-size: 14pt; font-weight: 600;">' + avgDuration.toFixed(2) + ' ms</div></div>';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;"><div style="color: #888; font-size: 8pt;">Медиана</div><div style="color: #ce93d8; font-size: 14pt; font-weight: 600;">' + medianDuration.toFixed(2) + ' ms</div></div>';
    html += '</div>';
    
    // Tabs
    html += '<div class="debug-tabs">';
    html += '<div class="debug-tab active" data-tab="time-sequential">По порядку</div>';
    html += '<div class="debug-tab" data-tab="time-sorted">По длительности</div>';
    html += '<div class="debug-tab" data-tab="time-visual">Визуальный</div>';
    html += '<div class="debug-tab" data-tab="time-summary">Сводка</div>';
    html += '</div>';
    
    // Tab: По порядку
    html += '<div class="debug-tab-content active" id="tab-time-sequential">';
    html += '<table class="debug-table"><thead><tr><th>Этап</th><th>Начало (мс)</th><th>Длительность (мс)</th><th>Процент</th></tr></thead><tbody>';
    
    for (const stage of stagesArray) {
        const percent = formatPercent(stage.durationMs, totalDurationMs);
        const color = getDurationColor(stage.durationMs, totalDurationMs);
        html += '<tr><td style="color: #e5e7eb;">' + stage.name + '</td><td style="color: #9ca3af;">' + stage.startMs.toFixed(1) + '</td><td style="color: ' + color + '; font-weight: 500;">' + stage.durationMs.toFixed(1) + '</td><td style="color: ' + color + ';">' + percent + '</td></tr>';
    }
    html += '</tbody></table></div>';
    
    // Tab: По длительности
    html += '<div class="debug-tab-content" id="tab-time-sorted">';
    html += '<div style="margin-bottom: 10px;"><span style="color: #888; font-size: 9pt;">▼ Быстрые</span> &nbsp;&nbsp; <span style="color: #888; font-size: 9pt;">Медленные ▲</span></div>';
    html += '<table class="debug-table"><thead><tr><th>Этап</th><th>Длительность (мс)</th><th>Процент</th><th>Накопление</th></tr></thead><tbody>';
    
    let cumulativePercent = 0;
    for (const stage of sortedByDuration) {
        const percent = formatPercent(stage.durationMs, totalDurationMs);
        cumulativePercent += parseFloat(percent);
        const color = getDurationColor(stage.durationMs, totalDurationMs);
        const barWidth = Math.min(cumulativePercent, 100);
        
        html += '<tr><td style="color: #e5e7eb;">' + stage.name + '</td><td style="color: ' + color + '; font-weight: 500;">' + stage.durationMs.toFixed(1) + '</td><td style="color: ' + color + ';">' + percent + '</td><td style="width: 150px;"><div style="background: #333; height: 8px; border-radius: 4px; overflow: hidden;"><div style="background: ' + color + '; height: 100%; width: ' + barWidth + '%;"></div></div><span style="color: #888; font-size: 8pt;">' + cumulativePercent.toFixed(1) + '%</span></td></tr>';
    }
    html += '</tbody></table></div>';
    
    // Tab: Визуальный
    html += '<div class="debug-tab-content" id="tab-time-visual">';
    
    // Основная временная шкала
    html += '<div style="margin-bottom: 20px;">';
    html += '<div style="color: #888; font-size: 8pt; margin-bottom: 5px;">Временная шкала (полная)</div>';
    html += '<div class="debug-timeline-visual" style="height: 60px; background: #252525; border-radius: 4px; position: relative; overflow: hidden;">';
    
    for (const stage of stagesArray) {
        const left = (stage.startMs / totalDurationMs) * 100;
        const width = Math.max((stage.durationMs / totalDurationMs) * 100, 0.5);
        const color = getDurationColor(stage.durationMs, totalDurationMs);
        
        html += '<div style="position: absolute; left: ' + left + '%; width: ' + width + '%; height: 100%; background: ' + color + '; opacity: 0.7; border-right: 1px solid #1e1e1e; display: flex; align-items: center; justify-content: center;" title="' + stage.name + ': ' + stage.durationMs.toFixed(1) + ' ms">';
        html += '<span style="color: #fff; font-size: 7pt; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%;">' + stage.name + '</span>';
        html += '</div>';
    }
    html += '</div></div>';
    
    // Детализация
    html += '<div style="color: #888; font-size: 8pt; margin-bottom: 5px;">Детализация по этапам</div>';
    for (const stage of stagesArray) {
        const percent = formatPercent(stage.durationMs, totalDurationMs);
        const color = getDurationColor(stage.durationMs, totalDurationMs);
        
        html += '<div style="margin-bottom: 8px;">';
        html += '<div style="display: flex; justify-content: space-between; margin-bottom: 2px;"><span style="color: #e5e7eb; font-size: 9pt;">' + stage.name + '</span><span style="color: ' + color + '; font-size: 9pt;">' + stage.durationMs.toFixed(1) + ' ms (' + percent + ')</span></div>';
        html += '<div style="background: #333; height: 12px; border-radius: 6px; overflow: hidden;"><div style="background: ' + color + '; height: 100%; width: ' + percent + '%;"></div></div>';
        html += '</div>';
    }
    html += '</div>';
    
    // Tab: Сводка
    html += '<div class="debug-tab-content" id="tab-time-summary">';
    
    // Топ медленных
    html += '<h4 style="color: #aaa; margin: 10px 0 10px;">Топ 5 самых медленных этапов</h4>';
    html += '<table class="debug-table"><thead><tr><th>#</th><th>Этап</th><th>Время (мс)</th><th>Процент</th></tr></thead><tbody>';
    
    sortedByDuration.slice(0, 5).forEach((stage, idx) => {
        const percent = formatPercent(stage.durationMs, totalDurationMs);
        const color = getDurationColor(stage.durationMs, totalDurationMs);
        html += '<tr><td style="color: #888;">' + (idx + 1) + '</td><td style="color: #e5e7eb;">' + stage.name + '</td><td style="color: ' + color + '; font-weight: 500;">' + stage.durationMs.toFixed(1) + '</td><td style="color: ' + color + ';">' + percent + '</td></tr>';
    });
    html += '</tbody></table>';
    
    // Топ быстрых
    const fastest = [...stagesArray].sort((a, b) => a.durationMs - b.durationMs).filter(s => s.durationMs > 0).slice(0, 5);
    
    html += '<h4 style="color: #aaa; margin: 15px 0 10px;">Топ 5 самых быстрых этапов</h4>';
    html += '<table class="debug-table"><thead><tr><th>#</th><th>Этап</th><th>Время (мс)</th></tr></thead><tbody>';
    
    fastest.forEach((stage, idx) => {
        html += '<tr><td style="color: #888;">' + (idx + 1) + '</td><td style="color: #e5e7eb;">' + stage.name + '</td><td style="color: #4caf50;">' + stage.durationMs.toFixed(2) + '</td></tr>';
    });
    html += '</tbody></table>';
    
    // Распределение
    html += '<h4 style="color: #aaa; margin: 15px 0 10px;">Распределение времени</h4>';
    html += '<table class="debug-table">';
    
    const fastCount = stagesArray.filter(s => s.durationMs < avgDuration).length;
    const slowCount = stagesArray.filter(s => s.durationMs >= avgDuration).length;
    const instantCount = stagesArray.filter(s => s.durationMs < 1).length;
    
    html += '<tr><td style="color: #9ca3af;">Мгновенные (< 1ms)</td><td style="color: #4caf50;">' + instantCount + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">Быстрые (< среднего)</td><td style="color: #8bc34a;">' + fastCount + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">Медленные (>= среднего)</td><td style="color: #ff9800;">' + slowCount + '</td></tr>';
    html += '</table>';
    
    html += '</div>';
    
    return html;
};
</script>
