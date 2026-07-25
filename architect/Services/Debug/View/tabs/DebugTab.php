<?php

declare(strict_types=1);

/**
 * Debug Tab - Вкладка пользовательских данных отладки
 */

?>
<script>
debugModules.debug = function() {
    const collector = debugData.collector;
    if (!collector || !collector.has_data) {
        return '<p style="color: #888;">Нет пользовательских данных</p>';
    }
    
    let html = '<div class="debug-tabs">' +
        '<div class="debug-tab active" data-tab="messages">Messages</div>' +
        '<div class="debug-tab" data-tab="timers">Timers</div>' +
        '<div class="debug-tab" data-tab="data">Data</div>' +
        '<div class="debug-tab" data-tab="counters">Counters</div>' +
        '<div class="debug-tab" data-tab="summary">Summary</div>' +
        '</div>';
    
    // Messages tab
    html += '<div class="debug-tab-content active" id="tab-messages">';
    if (collector.messages.length > 0) {
        html += '<table class="debug-table"><thead><tr><th>Категория</th><th>Уровень</th><th>Сообщение</th></tr></thead><tbody>';
        collector.messages.forEach(m => {
            html += '<tr><td style="color: #9ca3af;">' + escapeHtml(m.category) + '</td><td class="level-' + m.level + '">' + m.level + '</td><td style="color: #e5e7eb;">' + escapeHtml(m.message) + '</td></tr>';
        });
        html += '</tbody></table>';
    } else {
        html += '<p style="color: #888;">Нет сообщений</p>';
    }
    html += '</div>';
    
    // Timers tab
    html += '<div class="debug-tab-content" id="tab-timers">';
    if (Object.keys(collector.timers).length > 0) {
        html += '<table class="debug-table"><thead><tr><th>Название</th><th>Категория</th><th>Время (мс)</th><th>Статус</th></tr></thead><tbody>';
        for (const [name, timer] of Object.entries(collector.timers)) {
            const duration = (timer.duration * 1000).toFixed(2);
            const color = timer.duration > 0.1 ? 'color: #ffc107;' : 'color: #4caf50;';
            html += '<tr><td style="color: #e5e7eb;">' + escapeHtml(name) + '</td><td style="color: #9ca3af;">' + escapeHtml(timer.category) + '</td><td style="' + color + '">' + duration + '</td><td style="color: #9ca3af;">' + timer.status + '</td></tr>';
        }
        html += '</tbody></table>';
    } else {
        html += '<p style="color: #888;">Нет таймеров</p>';
    }
    html += '</div>';
    
    // Data tab
    html += '<div class="debug-tab-content" id="tab-data">';
    if (Object.keys(collector.data).length > 0) {
        for (const [category, items] of Object.entries(collector.data)) {
            html += '<h4 style="margin: 10px 0 5px; color: #e5e7eb;">' + escapeHtml(category) + '</h4>';
            items.forEach(item => {
                html += '<div style="padding: 5px; background: #252525; margin-bottom: 5px; color: #e5e7eb;">';
                if (item.description) {
                    html += '<div style="color: #888; font-size: 8pt;">' + escapeHtml(item.description) + '</div>';
                }
                html += '<pre style="margin: 5px 0; font-size: 8pt; white-space: pre-wrap; color: #ce93d8;">' + escapeHtml(JSON.stringify(item.data, null, 2)) + '</pre>';
                html += '</div>';
            });
        }
    } else {
        html += '<p style="color: #888;">Нет данных</p>';
    }
    html += '</div>';
    
    // Counters tab
    html += '<div class="debug-tab-content" id="tab-counters">';
    if (collector.counters && Object.keys(collector.counters).length > 0) {
        html += '<table class="debug-table"><thead><tr><th>Категория</th><th>Счётчик</th><th>Значение</th></tr></thead><tbody>';
        for (const [key, counter] of Object.entries(collector.counters)) {
            html += '<tr><td style="color: #9ca3af;">' + escapeHtml(counter.category) + '</td><td style="color: #e5e7eb;">' + escapeHtml(counter.name) + '</td><td style="color: #4caf50;">' + counter.value + '</td></tr>';
        }
        html += '</tbody></table>';
    } else {
        html += '<p style="color: #888;">Нет счётчиков</p>';
    }
    html += '</div>';
    
    // Summary tab
    html += '<div class="debug-tab-content" id="tab-summary">';
    html += '<h4 style="color: #aaa; margin: 10px 0 5px;">Топ таймеров</h4>';
    if (collector.top_timers && collector.top_timers.length > 0) {
        html += '<table class="debug-table"><thead><tr><th>Название</th><th>Время (мс)</th></tr></thead><tbody>';
        collector.top_timers.forEach(t => {
            html += '<tr><td style="color: #e5e7eb;">' + escapeHtml(Object.keys(collector.timers).find(k => collector.timers[k] === t) || 'unknown') + '</td><td style="color: #4caf50;">' + (t.duration * 1000).toFixed(2) + '</td></tr>';
        });
        html += '</tbody></table>';
    } else {
        html += '<p style="color: #888;">Нет данных</p>';
    }
    
    html += '<h4 style="color: #aaa; margin: 10px 0 5px;">Статистика уровней</h4>';
    const stats = collector.level_stats;
    html += '<table class="debug-table">' +
        '<tr><td style="color: #9ca3af;">Debug</td><td class="level-debug">' + (stats.debug || 0) + '</td></tr>' +
        '<tr><td style="color: #9ca3af;">Info</td><td class="level-info">' + (stats.info || 0) + '</td></tr>' +
        '<tr><td style="color: #9ca3af;">Warning</td><td class="level-warning">' + (stats.warning || 0) + '</td></tr>' +
        '<tr><td style="color: #9ca3af;">Error</td><td class="level-error">' + (stats.error || 0) + '</td></tr>' +
        '</table>';
    html += '</div>';
    
    return html;
};
</script>
