<?php

declare(strict_types=1);

/**
 * Database Tab - Вкладка SQL-запросов
 *
 * Вкладки:
 * - Сводка: общая статистика по запросам
 * - Все запросы: полный список с деталями
 * - Медленные: запросы > 100ms
 * - По источникам:分组 по database/axiom
 */

?>
<script>
debugModules.database = function() {
    let queries = debugData.queries;
    if (!Array.isArray(queries)) {
        if (queries && typeof queries === 'object') {
            queries = Object.values(queries);
        } else {
            queries = [];
        }
    }
    if (queries.length === 0) {
        return '<p style="color: #888;">Нет SQL-запросов</p>';
    }
    
    // Statistics
    const totalQueries = queries.length;
    const slowQueries = queries.filter(q => q.is_slow).length;
    const totalTime = queries.reduce((sum, q) => sum + (q.duration || 0), 0);
    const avgTime = totalTime / totalQueries;
    
    // Group by source
    const bySource = {};
    queries.forEach(q => {
        const source = q.source || 'database';
        if (!bySource[source]) bySource[source] = [];
        bySource[source].push(q);
    });
    
    // Slow queries
    const slowQueryList = queries.filter(q => q.is_slow);
    
    // Query types
    const selectCount = queries.filter(q => /^\s*SELECT/i.test(q.query || '')).length;
    const insertCount = queries.filter(q => /^\s*INSERT/i.test(q.query || '')).length;
    const updateCount = queries.filter(q => /^\s*UPDATE/i.test(q.query || '')).length;
    const deleteCount = queries.filter(q => /^\s*DELETE/i.test(q.query || '')).length;
    const otherCount = totalQueries - selectCount - insertCount - updateCount - deleteCount;
    
    let html = '<div class="debug-tabs">' +
        '<div class="debug-tab active" data-tab="db-summary">Сводка</div>' +
        '<div class="debug-tab" data-tab="db-all">Все запросы</div>' +
        '<div class="debug-tab" data-tab="db-slow">Медленные (' + slowQueries + ')</div>' +
        '<div class="debug-tab" data-tab="db-sources">По источникам</div>' +
        '</div>';
    
    // Tab: Summary
    html += '<div class="debug-tab-content active" id="tab-db-summary">';
    html += '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 15px;">';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;">';
    html += '<div style="color: #888; font-size: 8pt;">Всего запросов</div>';
    html += '<div style="color: #64b5f6; font-size: 14pt; font-weight: 600;">' + totalQueries + '</div>';
    html += '</div>';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;">';
    html += '<div style="color: #888; font-size: 8pt;">Медленных</div>';
    html += '<div style="color: ' + (slowQueries > 0 ? '#f44336' : '#4caf50') + '; font-size: 14pt; font-weight: 600;">' + slowQueries + '</div>';
    html += '</div>';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;">';
    html += '<div style="color: #888; font-size: 8pt;">Общее время</div>';
    html += '<div style="color: #e5e7eb; font-size: 14pt; font-weight: 600;">' + (totalTime * 1000).toFixed(2) + ' ms</div>';
    html += '</div>';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;">';
    html += '<div style="color: #888; font-size: 8pt;">Среднее время</div>';
    html += '<div style="color: #ce93d8; font-size: 14pt; font-weight: 600;">' + (avgTime * 1000).toFixed(2) + ' ms</div>';
    html += '</div>';
    html += '</div>';
    
    // Query types breakdown
    html += '<h4 style="color: #aaa; margin: 10px 0 10px;">Типы запросов</h4>';
    html += '<table class="debug-table">';
    html += '<tr><td style="color: #9ca3af;">SELECT</td><td style="color: #4caf50;">' + selectCount + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">INSERT</td><td style="color: #2196f3;">' + insertCount + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">UPDATE</td><td style="color: #ff9800;">' + updateCount + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">DELETE</td><td style="color: #f44336;">' + deleteCount + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">Другие</td><td style="color: #9e9e9e;">' + otherCount + '</td></tr>';
    html += '</table>';
    html += '</div>';
    
    // Tab: All queries
    html += '<div class="debug-tab-content" id="tab-db-all">';
    html += '<table class="debug-table"><thead><tr><th>#</th><th>Запрос</th><th>Время (мс)</th><th>Тип</th></tr></thead><tbody>';
    queries.forEach((q, i) => {
        const slowClass = q.is_slow ? 'slow-query' : '';
        const duration = q.duration || 0;
        const durationMs = (duration * 1000).toFixed(2);
        const durationColor = duration > 0.1 ? '#f44336' : '#4caf50';
        const source = q.source || 'database';
        const sourceColor = source === 'axiom' ? '#ce93d8' : '#64b5f6';
        
        html += '<tr class="' + slowClass + '">' +
            '<td style="color: #9ca3af;">' + (i + 1) + '</td>' +
            '<td style="max-width: 350px; overflow: hidden; text-overflow: ellipsis; color: #e5e7eb;">' + escapeHtml(q.query) + '</td>' +
            '<td style="color: ' + durationColor + ';">' + durationMs + '</td>' +
            '<td style="color: ' + sourceColor + '; font-size: 8pt;">' + source + '</td>' +
            '</tr>';
    });
    html += '</tbody></table>';
    html += '</div>';
    
    // Tab: Slow queries
    html += '<div class="debug-tab-content" id="tab-db-slow">';
    if (slowQueryList.length > 0) {
        html += '<table class="debug-table"><thead><tr><th>#</th><th>Запрос</th><th>Время (мс)</th><th>Тип</th></tr></thead><tbody>';
        slowQueryList.forEach((q, i) => {
            const duration = q.duration || 0;
            const durationMs = (duration * 1000).toFixed(2);
            const source = q.source || 'database';
            const sourceColor = source === 'axiom' ? '#ce93d8' : '#64b5f6';
            
            html += '<tr style="background: rgba(244, 67, 54, 0.1);">' +
                '<td style="color: #f44336;">' + (i + 1) + '</td>' +
                '<td style="max-width: 350px; overflow: hidden; text-overflow: ellipsis; color: #e5e7eb;">' + escapeHtml(q.query) + '</td>' +
                '<td style="color: #f44336; font-weight: 600;">' + durationMs + '</td>' +
                '<td style="color: ' + sourceColor + '; font-size: 8pt;">' + source + '</td>' +
                '</tr>';
        });
        html += '</tbody></table>';
    } else {
        html += '<p style="color: #4caf50;">Нет медленных запросов</p>';
    }
    html += '</div>';
    
    // Tab: By sources
    html += '<div class="debug-tab-content" id="tab-db-sources">';
    for (const [source, sourceQueries] of Object.entries(bySource)) {
        const sourceTime = sourceQueries.reduce((sum, q) => sum + (q.duration || 0), 0);
        const sourceColor = source === 'axiom' ? '#ce93d8' : '#64b5f6';
        
        html += '<h4 style="color: ' + sourceColor + '; margin: 10px 0 5px;">' + source.toUpperCase() + ' (' + sourceQueries.length + ' запросов, ' + (sourceTime * 1000).toFixed(2) + ' ms)</h4>';
        html += '<table class="debug-table"><thead><tr><th>#</th><th>Запрос</th><th>Время (мс)</th></tr></thead><tbody>';
        sourceQueries.forEach((q, i) => {
            const duration = q.duration || 0;
            const durationColor = duration > 0.1 ? '#f44336' : '#4caf50';
            html += '<tr>' +
                '<td style="color: #9ca3af;">' + (i + 1) + '</td>' +
                '<td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; color: #e5e7eb;">' + escapeHtml(q.query) + '</td>' +
                '<td style="color: ' + durationColor + ';">' + (duration * 1000).toFixed(2) + '</td>' +
                '</tr>';
        });
        html += '</tbody></table>';
    }
    html += '</div>';
    
    return html;
};
</script>
