<?php

declare(strict_types=1);

/**
 * Logs Tab - Вкладка логов
 *
 * Добавлена фильтрация по уровням логов (info, warning, error, debug и др.)
 * Панель фильтров позволяет включать/отключать отображение логов по уровням.
 */

?>
<script>
debugModules.logs = function() {
    try {
        // Внутренние логи (из debugData.logs)
        let internalLogs = debugData.logs;
        if (!Array.isArray(internalLogs)) {
            if (internalLogs && typeof internalLogs === 'object') {
                internalLogs = Object.values(internalLogs);
            } else {
                internalLogs = [];
            }
        }
        // Добавляем источник
        internalLogs = internalLogs.map(log => ({
            ...log,
            source: 'internal',
            level: log.category || 'info'
        }));

        // Пользовательские логи из коллектора (debugData.collector?.messages)
        let customLogs = [];
        if (debugData.collector && debugData.collector.messages && Array.isArray(debugData.collector.messages)) {
            customLogs = debugData.collector.messages.map(msg => ({
                time: msg.time,
                level: msg.level || msg.category || 'info',
                message: msg.message,
                source: 'custom',
                context: msg.context
            }));
        }

        // Системные логи (из debugData.system_logs)
        let systemLogs = [];
        if (debugData.system_logs && Array.isArray(debugData.system_logs)) {
            systemLogs = debugData.system_logs.map(log => ({
                time: log.time,
                level: log.level || 'info',
                message: log.message,
                source: 'system',
                channel: log.channel
            }));
        }

        // Объединяем все логи
        let allLogs = [...internalLogs, ...customLogs, ...systemLogs];

        // Собираем уникальные уровни логов
        const allLevels = new Set();
        allLogs.forEach(log => allLevels.add(log.level || 'info'));
        internalLogs.forEach(log => allLevels.add(log.level || 'info'));
        customLogs.forEach(log => allLevels.add(log.level || 'info'));
        systemLogs.forEach(log => allLevels.add(log.level || 'info'));
        const levels = Array.from(allLevels).sort();

        // Сортируем по времени (убывание - самые новые сверху)
        allLogs.sort((a, b) => b.time - a.time);
        internalLogs.sort((a, b) => b.time - a.time);
        customLogs.sort((a, b) => b.time - a.time);
        systemLogs.sort((a, b) => b.time - a.time);

        // Функция рендеринга таблицы логов
        function renderLogsTable(logs, showSource = true, tableId = '') {
            if (logs.length === 0) {
                return '<p style="color: #888;">Нет логов</p>';
            }
            
            let html = '<table class="debug-table" ' + (tableId ? 'id="' + tableId + '"' : '') + '><thead><tr><th>Время</th><th>Уровень</th>';
            if (showSource) {
                html += '<th>Источник</th>';
            }
            html += '<th>Сообщение</th></tr></thead><tbody>';
            
            logs.forEach(log => {
                const level = log.level || 'info';
                const levelClass = 'level-' + level;
                const timeMs = (log.time * 1000).toFixed(1);
                const sourceLabel = log.source === 'internal' ? 'Внутр.' : log.source === 'custom' ? 'Польз.' : 'Систем.';
                const sourceClass = 'source-' + log.source;
                html += '<tr data-level="' + level + '">' +
                    '<td style="color: #9ca3af;">' + timeMs + ' ms</td>' +
                    '<td class="' + levelClass + '">' + level + '</td>';
                if (showSource) {
                    html += '<td class="' + sourceClass + '" style="color: #cbd5e1;">' + sourceLabel + '</td>';
                }
                html += '<td style="color: #e5e7eb;">' + escapeHtml(log.message) + '</td>' +
                    '</tr>';
            });
            
            html += '</tbody></table>';
            return html;
        }

        // Глобальная функция фильтрации логов
        window.filterLogsTable = function() {
            const activeTab = document.querySelector(".debug-tab-content.active");
            if (!activeTab) return;
            const table = activeTab.querySelector("table.debug-table");
            if (!table) return;
            const checkedLevels = Array.from(document.querySelectorAll(".log-level-filter:checked")).map(cb => cb.value);
            const rows = table.querySelectorAll("tbody tr");
            rows.forEach(row => {
                const level = row.getAttribute("data-level");
                const visible = checkedLevels.length === 0 || checkedLevels.includes(level);
                row.style.display = visible ? "" : "none";
            });
        };

        // Панель фильтров по уровням
        let filtersHtml = '';
        if (levels.length > 0) {
            filtersHtml = '<div class="log-filters" style="margin-bottom: 15px; padding: 10px; background: #2a2a2a; border-radius: 4px;">';
            filtersHtml += '<strong style="color: #ccc; margin-right: 10px;">Уровни:</strong>';
            levels.forEach(level => {
                filtersHtml += '<label style="color: #ccc; margin-right: 15px; cursor: pointer;">';
                filtersHtml += '<input type="checkbox" class="log-level-filter" value="' + level + '" checked onchange="filterLogsTable()"> ' + level;
                filtersHtml += '</label>';
            });
            filtersHtml += '<button onclick="document.querySelectorAll(\'.log-level-filter\').forEach(cb => cb.checked = true); filterLogsTable();" style="margin-left: 10px; padding: 2px 8px; background: #3a3a3a; color: #ccc; border: 1px solid #555; border-radius: 3px; cursor: pointer;">Выбрать все</button>';
            filtersHtml += '<button onclick="document.querySelectorAll(\'.log-level-filter\').forEach(cb => cb.checked = false); filterLogsTable();" style="margin-left: 5px; padding: 2px 8px; background: #3a3a3a; color: #ccc; border: 1px solid #555; border-radius: 3px; cursor: pointer;">Снять все</button>';
            filtersHtml += '</div>';
        }

        // Создаем вкладки
        let html = filtersHtml + '<div class="debug-tabs">' +
            '<div class="debug-tab active" data-tab="all">Все</div>' +
            '<div class="debug-tab" data-tab="internal">Внутренние</div>' +
            '<div class="debug-tab" data-tab="custom">Пользовательские</div>' +
            '<div class="debug-tab" data-tab="system">Системные</div>' +
            '</div>';
        
        // Контент для вкладки "Все"
        html += '<div class="debug-tab-content active" id="tab-all">';
        if (allLogs.length === 0) {
            html += '<p style="color: #888;">Нет логов</p>';
        } else {
            html += renderLogsTable(allLogs, true, 'logs-table-all');
        }
        html += '</div>';
        
        // Контент для вкладки "Внутренние"
        html += '<div class="debug-tab-content" id="tab-internal">';
        if (internalLogs.length === 0) {
            html += '<p style="color: #888;">Нет внутренних логов</p>';
        } else {
            html += renderLogsTable(internalLogs, false, 'logs-table-internal');
        }
        html += '</div>';
        
        // Контент для вкладки "Пользовательские"
        html += '<div class="debug-tab-content" id="tab-custom">';
        if (customLogs.length === 0) {
            html += '<p style="color: #888;">Нет пользовательских логов</p>';
        } else {
            html += renderLogsTable(customLogs, false, 'logs-table-custom');
        }
        html += '</div>';
        
        // Контент для вкладки "Системные"
        html += '<div class="debug-tab-content" id="tab-system">';
        if (systemLogs.length === 0) {
            html += '<p style="color: #888;">Нет системных логов</p>';
        } else {
            html += renderLogsTable(systemLogs, false, 'logs-table-system');
        }
        html += '</div>';
        
        return html;
    } catch (e) {
        return '<div style="color: red;">Ошибка в logs модуле: ' + e.message + '</div>';
    }
};
</script>
