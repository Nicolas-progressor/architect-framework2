<?php

declare(strict_types=1);

/**
 * Memory Tab - Вкладка памяти
 */

?>
<script>
debugModules.memory = function() {
    const peak = debugData.memory_peak;
    const limit = debugData.memory_limit;
    const percent = debugData.memory_percent;
    const stageMemory = debugData.stage_memory || {};
    
    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }
    
    function getMemoryColor(mem, total) {
        if (total === 0) return '#4caf50';
        const p = (mem / total) * 100;
        if (p < 30) return '#4caf50';
        if (p < 60) return '#8bc34a';
        if (p < 80) return '#ffc107';
        return '#f44336';
    }
    
    // Сбор данных об этапах
    const stagesArray = Object.entries(stageMemory).map(([name, mem]) => ({
        name,
        start: mem.start || 0,
        end: mem.end || 0,
        peak: mem.peak || 0,
        delta: (mem.end || 0) - (mem.start || 0)
    }));
    
    // Общая память
    const totalDelta = stagesArray.reduce((sum, s) => sum + s.delta, 0);
    const maxMemory = Math.max(...stagesArray.map(s => s.peak), peak);
    
    // Метрики
    const memoryUsages = stagesArray.map(s => s.delta).filter(d => d > 0);
    const avgUsage = memoryUsages.length > 0 ? memoryUsages.reduce((a, b) => a + b, 0) / memoryUsages.length : 0;
    const sortedUsages = [...memoryUsages].sort((a, b) => a - b);
    const medianUsage = sortedUsages.length > 0 
        ? (sortedUsages.length % 2 === 0 
            ? (sortedUsages[sortedUsages.length / 2 - 1] + sortedUsages[sortedUsages.length / 2]) / 2 
            : sortedUsages[Math.floor(sortedUsages.length / 2)]) 
        : 0;
    
    let html = '';
    
    // === Статистика ===
    html += '<div class="debug-time-stats">';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;"><div style="color: #888; font-size: 8pt;">Пиковая</div><div style="color: #64b5f6; font-size: 14pt; font-weight: 600;">' + formatBytes(peak) + '</div></div>';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;"><div style="color: #888; font-size: 8pt;">Лимит</div><div style="color: #e5e7eb; font-size: 14pt; font-weight: 600;">' + (limit > 0 ? formatBytes(limit) : '∞') + '</div></div>';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;"><div style="color: #888; font-size: 8pt;">Использовано</div><div style="color: ' + (percent > 80 ? '#f44336' : '#4caf50') + '; font-size: 14pt; font-weight: 600;">' + percent + '%</div></div>';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;"><div style="color: #888; font-size: 8pt;">Прирост</div><div style="color: #ce93d8; font-size: 14pt; font-weight: 600;">' + formatBytes(totalDelta) + '</div></div>';
    html += '</div>';
    
    // === Tabs ===
    html += '<div class="debug-tabs">';
    html += '<div class="debug-tab active" data-tab="mem-overview">Обзор</div>';
    html += '<div class="debug-tab" data-tab="mem-stages">По этапам</div>';
    html += '<div class="debug-tab" data-tab="mem-visual">Визуальный</div>';
    html += '<div class="debug-tab" data-tab="mem-summary">Сводка</div>';
    html += '</div>';
    
    // === Tab: Обзор ===
    html += '<div class="debug-tab-content active" id="tab-mem-overview">';
    html += '<table class="debug-table">';
    html += '<tr><td>Пиковая память</td><td style="color: #4caf50;">' + formatBytes(peak) + '</td></tr>';
    html += '<tr><td>Текущая память (JS)</td><td style="color: #4caf50;">' + formatBytes(window.performance?.memory?.usedJSHeapSize || 0) + '</td></tr>';
    html += '<tr><td>Лимит памяти PHP</td><td style="color: #9ca3af;">' + (limit > 0 ? formatBytes(limit) : 'Без ограничений') + '</td></tr>';
    html += '<tr><td>Использовано от лимита</td><td style="color: ' + (percent > 80 ? '#f44336' : '#4caf50') + ';">' + percent + '%</td></tr>';
    html += '<tr><td>Общий прирост памяти</td><td style="color: #ce93d8;">' + formatBytes(totalDelta) + '</td></tr>';
    html += '<tr><td>Доступно JS heap</td><td style="color: #9ca3af;">' + formatBytes(window.performance?.memory?.jsHeapSizeLimit || 0) + '</td></tr>';
    html += '</table>';
    
    // Progress bar использования
    html += '<div style="margin-top: 15px;">';
    html += '<div style="color: #888; font-size: 8pt; margin-bottom: 5px;">Использование памяти</div>';
    html += '<div style="background: #333; height: 20px; border-radius: 10px; overflow: hidden;">';
    html += '<div style="background: linear-gradient(90deg, ' + getMemoryColor(peak, limit > 0 ? limit : peak * 2) + ', ' + getMemoryColor(peak, limit > 0 ? limit : peak * 2) + 'dd); height: 100%; width: ' + (limit > 0 ? Math.min(percent, 100) : 50) + '%;"></div>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    
    // === Tab: По этапам ===
    html += '<div class="debug-tab-content" id="tab-mem-stages">';
    if (stagesArray.length > 0) {
        html += '<table class="debug-table"><thead><tr><th>Этап</th><th>Старт</th><th>Конец</th><th>Прирост</th></tr></thead><tbody>';
        
        for (const stage of stagesArray) {
            const color = stage.delta > 0 ? (stage.delta > 1024 * 1024 ? '#f44336' : '#4caf50') : '#888';
            html += '<tr>';
            html += '<td style="color: #e5e7eb;">' + stage.name + '</td>';
            html += '<td style="color: #9ca3af;">' + formatBytes(stage.start) + '</td>';
            html += '<td style="color: #9ca3af;">' + formatBytes(stage.end) + '</td>';
            html += '<td style="color: ' + color + '; font-weight: 500;">' + (stage.delta > 0 ? '+' : '') + formatBytes(stage.delta) + '</td>';
            html += '</tr>';
        }
        html += '</tbody></table>';
    } else {
        html += '<p style="color: #888;">Нет данных по этапам</p>';
    }
    html += '</div>';
    
    // === Tab: Визуальный ===
    html += '<div class="debug-tab-content" id="tab-mem-visual">';
    
    // Общая шкала
    html += '<div style="margin-bottom: 20px;">';
    html += '<div style="color: #888; font-size: 8pt; margin-bottom: 5px;">Шкала использования памяти</div>';
    html += '<div style="height: 60px; background: #252525; border-radius: 4px; position: relative; overflow: hidden;">';
    
    // Лимит
    if (limit > 0) {
        const limitPos = 100;
        html += '<div style="position: absolute; right: 0; top: 0; bottom: 0; width: 2px; background: #f44336; z-index: 10;"></div>';
    }
    
    for (const stage of stagesArray) {
        if (stage.peak > 0) {
            const height = limit > 0 ? (stage.peak / limit) * 100 : (stage.peak / maxMemory) * 100;
            const color = getMemoryColor(stage.peak, limit > 0 ? limit : maxMemory);
            html += '<div style="position: absolute; bottom: 0; left: ' + (stagesArray.indexOf(stage) * (100 / stagesArray.length)) + '%; width: ' + (90 / stagesArray.length) + '%; height: ' + Math.min(height, 100) + '%; background: ' + color + '; opacity: 0.7;" title="' + stage.name + ': ' + formatBytes(stage.peak) + '"></div>';
        }
    }
    html += '</div>';
    html += '</div>';
    
    // Детализация по этапам
    html += '<div style="color: #888; font-size: 8pt; margin-bottom: 5px;">Детализация по этапам</div>';
    const sortedByDelta = [...stagesArray].sort((a, b) => b.delta - a.delta);
    
    for (const stage of sortedByDelta) {
        if (stage.delta !== 0) {
            const percentStage = limit > 0 ? (stage.delta / limit) * 100 : (stage.delta / totalDelta) * 100;
            const color = stage.delta > 0 ? getMemoryColor(stage.delta, totalDelta) : '#888';
            
            html += '<div style="margin-bottom: 8px;">';
            html += '<div style="display: flex; justify-content: space-between; margin-bottom: 2px;">';
            html += '<span style="color: #e5e7eb; font-size: 9pt;">' + stage.name + '</span>';
            html += '<span style="color: ' + color + '; font-size: 9pt;">' + (stage.delta > 0 ? '+' : '') + formatBytes(stage.delta) + '</span>';
            html += '</div>';
            html += '<div style="background: #333; height: 12px; border-radius: 6px; overflow: hidden;">';
            html += '<div style="background: ' + color + '; height: 100%; width: ' + Math.min(percentStage, 100) + '%;"></div>';
            html += '</div>';
            html += '</div>';
        }
    }
    html += '</div>';
    
    // === Tab: Сводка ===
    html += '<div class="debug-tab-content" id="tab-mem-summary">';
    
    // Топ по приросту
    html += '<h4 style="color: #aaa; margin: 10px 0 10px;">Топ 5 этапов по приросту памяти</h4>';
    html += '<table class="debug-table"><thead><tr><th>#</th><th>Этап</th><th>Прирост</th><th>Пиковая</th></tr></thead><tbody>';
    
    sortedByDelta.slice(0, 5).forEach((stage, idx) => {
        const color = stage.delta > 1024 * 1024 ? '#f44336' : '#4caf50';
        html += '<tr>';
        html += '<td style="color: #888;">' + (idx + 1) + '</td>';
        html += '<td style="color: #e5e7eb;">' + stage.name + '</td>';
        html += '<td style="color: ' + color + '; font-weight: 500;">' + (stage.delta > 0 ? '+' : '') + formatBytes(stage.delta) + '</td>';
        html += '<td style="color: #9ca3af;">' + formatBytes(stage.peak) + '</td>';
        html += '</tr>';
    });
    html += '</tbody></table>';
    
    // Распределение
    html += '<h4 style="color: #aaa; margin: 15px 0 10px;">Распределение</h4>';
    html += '<table class="debug-table">';
    
    const highMemCount = stagesArray.filter(s => s.delta > 1024 * 1024).length;
    const lowMemCount = stagesArray.filter(s => s.delta > 0 && s.delta <= 1024 * 1024).length;
    const noChangeCount = stagesArray.filter(s => s.delta === 0).length;
    
    html += '<tr><td style="color: #9ca3af;">Большой прирост (>1MB)</td><td style="color: #f44336;">' + highMemCount + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">Умеренный прирост (≤1MB)</td><td style="color: #8bc34a;">' + lowMemCount + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">Без изменения</td><td style="color: #888;">' + noChangeCount + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">Средний прирост</td><td style="color: #ce93d8;">' + formatBytes(avgUsage) + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">Медиана прироста</td><td style="color: #ce93d8;">' + formatBytes(medianUsage) + '</td></tr>';
    html += '</table>';
    
    html += '</div>';
    
    return html;
};
</script>
