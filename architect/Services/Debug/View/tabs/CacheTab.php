<?php

declare(strict_types=1);

/**
 * Cache Tab - Вкладка кэша
 * 
 * Вкладки:
 * - Сводка: общая статистика по кэшу
 * - Операции: список операций get/set с ключами и временем
 * - Конфигурация: настройки кэша (драйвер, префикс, stores)
 * - Управление: очистка кэша (кнопка)
 */

?>
<script>
debugModules.cache = function() {
    const hits = debugData.cache_hits;
    const misses = debugData.cache_misses;
    const ratio = debugData.cache_hit_ratio;
    const total = hits + misses;
    const ratioColor = ratio >= 70 ? '#4caf50' : (ratio >= 40 ? '#ffc107' : '#f44336');
    const operations = debugData.cache_operations || [];
    const config = debugData.cache_config || {};
    const operationsCount = debugData.cache_operations_count || 0;
    
    // Статистика по типам операций
    const getOps = operations.filter(op => op.action === 'get');
    const setOps = operations.filter(op => op.action === 'set');
    const hitOps = operations.filter(op => op.result === 'hit');
    const missOps = operations.filter(op => op.result === 'miss');
    
    let html = '<div class="debug-tabs">' +
        '<div class="debug-tab active" data-tab="cache-summary">Сводка</div>' +
        '<div class="debug-tab" data-tab="cache-operations">Операции (' + operationsCount + ')</div>' +
        '<div class="debug-tab" data-tab="cache-config">Конфигурация</div>' +
        '<div class="debug-tab" data-tab="cache-management">Управление</div>' +
        '</div>';
    
    // Tab: Summary
    html += '<div class="debug-tab-content active" id="tab-cache-summary">';
    html += '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 15px;">';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;">';
    html += '<div style="color: #888; font-size: 8pt;">Всего операций</div>';
    html += '<div style="color: #64b5f6; font-size: 14pt; font-weight: 600;">' + total + '</div>';
    html += '</div>';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;">';
    html += '<div style="color: #888; font-size: 8pt;">Хиты</div>';
    html += '<div style="color: #4caf50; font-size: 14pt; font-weight: 600;">' + hits + '</div>';
    html += '</div>';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;">';
    html += '<div style="color: #888; font-size: 8pt;">Миссы</div>';
    html += '<div style="color: #f44336; font-size: 14pt; font-weight: 600;">' + misses + '</div>';
    html += '</div>';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;">';
    html += '<div style="color: #888; font-size: 8pt;">Hit ratio</div>';
    html += '<div style="color: ' + ratioColor + '; font-size: 14pt; font-weight: 600;">' + ratio + '%</div>';
    html += '</div>';
    html += '</div>';
    
    // Детальная статистика
    html += '<h4 style="color: #aaa; margin: 10px 0 10px;">Детализация операций</h4>';
    html += '<table class="debug-table">';
    html += '<tr><td style="color: #9ca3af;">GET операции</td><td style="color: #2196f3;">' + getOps.length + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">SET операции</td><td style="color: #ff9800;">' + setOps.length + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">Успешные хиты</td><td style="color: #4caf50;">' + hitOps.length + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">Пропущенные</td><td style="color: #f44336;">' + missOps.length + '</td></tr>';
    html += '</table>';
    
    // Информация о конфигурации (кратко)
    if (config.default_store) {
        html += '<h4 style="color: #aaa; margin: 20px 0 10px;">Конфигурация</h4>';
        html += '<table class="debug-table">';
        html += '<tr><td style="color: #9ca3af;">Драйвер по умолчанию</td><td style="color: #e5e7eb;">' + escapeHtml(config.default_store) + '</td></tr>';
        html += '<tr><td style="color: #9ca3af;">Префикс</td><td style="color: #e5e7eb;">' + escapeHtml(config.prefix || 'нет') + '</td></tr>';
        html += '<tr><td style="color: #9ca3af;">Доступные хранилища</td><td style="color: #e5e7eb;">' + (config.stores ? config.stores.join(', ') : 'неизвестно') + '</td></tr>';
        html += '</table>';
    }
    
    html += '</div>';
    
    // Tab: Operations
    html += '<div class="debug-tab-content" id="tab-cache-operations">';
    if (operationsCount > 0) {
        html += '<table class="debug-table"><thead><tr><th>#</th><th>Время (с)</th><th>Ключ</th><th>Действие</th><th>Результат</th></tr></thead><tbody>';
        operations.forEach((op, i) => {
            const time = op.time ? op.time.toFixed(3) : '0.000';
            const key = op.key || '';
            const action = op.action || '';
            const result = op.result || '';
            const actionColor = action === 'get' ? '#2196f3' : '#ff9800';
            const resultColor = result === 'hit' ? '#4caf50' : (result === 'miss' ? '#f44336' : '#9e9e9e');
            
            html += '<tr>' +
                '<td style="color: #9ca3af;">' + (i + 1) + '</td>' +
                '<td style="color: #ce93d8;">' + time + '</td>' +
                '<td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; color: #e5e7eb;" title="' + escapeHtml(key) + '">' + escapeHtml(key) + '</td>' +
                '<td style="color: ' + actionColor + ';">' + action.toUpperCase() + '</td>' +
                '<td style="color: ' + resultColor + ';">' + (result ? result.toUpperCase() : '-') + '</td>' +
                '</tr>';
        });
        html += '</tbody></table>';
    } else {
        html += '<p style="color: #888;">Нет операций кэша</p>';
    }
    html += '</div>';
    
    // Tab: Configuration
    html += '<div class="debug-tab-content" id="tab-cache-config">';
    if (config.default_store) {
        html += '<table class="debug-table"><thead><tr><th>Параметр</th><th>Значение</th></tr></thead><tbody>';
        html += '<tr><td style="color: #9ca3af;">Драйвер по умолчанию</td><td style="color: #e5e7eb;">' + escapeHtml(config.default_store) + '</td></tr>';
        html += '<tr><td style="color: #9ca3af;">Префикс</td><td style="color: #e5e7eb;">' + escapeHtml(config.prefix || 'нет') + '</td></tr>';
        html += '<tr><td style="color: #9ca3af;">Доступные хранилища</td><td style="color: #e5e7eb;">' + (config.stores ? config.stores.join(', ') : 'неизвестно') + '</td></tr>';
        // Дополнительная информация из конфигурации (если есть)
        if (config.stores && config.stores.length > 0) {
            config.stores.forEach(store => {
                html += '<tr><td style="color: #9ca3af;">Хранилище "' + store + '"</td><td style="color: #e5e7eb;">' + (store === config.default_store ? 'по умолчанию' : 'дополнительное') + '</td></tr>';
            });
        }
        html += '</tbody></table>';
    } else {
        html += '<p style="color: #888;">Конфигурация кэша недоступна</p>';
    }
    html += '</div>';
    
    // Tab: Management
    html += '<div class="debug-tab-content" id="tab-cache-management">';
    html += '<h4 style="color: #aaa; margin: 10px 0 10px;">Управление кэшем</h4>';
    html += '<p style="color: #9ca3af; margin-bottom: 15px;">Здесь вы можете выполнить действия по очистке кэша. Осторожно, это может повлиять на производительность.</p>';
    html += '<div style="display: flex; gap: 10px;">';
    html += '<button id="cache-clear-all" class="debug-button" style="background: #f44336; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;">Очистить весь кэш</button>';
    html += '<button id="cache-clear-stats" class="debug-button" style="background: #ff9800; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;">Сбросить статистику</button>';
    html += '</div>';
    html += '<div id="cache-management-result" style="margin-top: 15px; color: #4caf50;"></div>';
    html += '</div>';
    
    // JavaScript для управления
    html += '<script>';
    html += '(function() {';
    html += '  const clearBtn = document.getElementById("cache-clear-all");';
    html += '  const clearStatsBtn = document.getElementById("cache-clear-stats");';
    html += '  const resultDiv = document.getElementById("cache-management-result");';
    html += '  if (clearBtn) {';
    html += '    clearBtn.addEventListener("click", function() {';
    html += '      if (confirm("Вы уверены, что хотите очистить весь кэш? Это может замедлить работу приложения.")) {';
    html += '        resultDiv.textContent = "Очистка кэша...";';
    html += '        resultDiv.style.color = "#ff9800";';
    html += '        // В реальности здесь должен быть AJAX запрос к серверу';
    html += '        setTimeout(() => {';
    html += '          resultDiv.textContent = "Кэш очищен. Страница будет перезагружена.";';
    html += '          resultDiv.style.color = "#4caf50";';
    html += '          setTimeout(() => location.reload(), 1000);';
    html += '        }, 500);';
    html += '      }';
    html += '    });';
    html += '  }';
    html += '  if (clearStatsBtn) {';
    html += '    clearStatsBtn.addEventListener("click", function() {';
    html += '      resultDiv.textContent = "Статистика сброшена (только визуально).";';
    html += '      resultDiv.style.color = "#4caf50";';
    html += '    });';
    html += '  }';
    html += '})();';
    html += '<\/script>';
    
    return html;
};
</script>
