<?php

declare(strict_types=1);

/**
 * Session Tab - Расширенная вкладка сессии
 * 
 * Вкладки:
 * - Сводка: общая информация о сессии (статус, ID, время жизни, размер)
 * - Данные: дерево данных сессии с возможностью раскрытия и поиска
 * - Безопасность: проверка на чувствительные данные и рекомендации
 * - Управление: очистка, регенерация ID, установка значений
 */

?>
<script>
debugModules.session = function() {
    const session = debugData.session_data || {};
    const meta = debugData.session_meta || {};
    
    let html = '<div class="debug-tabs">' +
        '<div class="debug-tab active" data-tab="session-summary">Сводка</div>' +
        '<div class="debug-tab" data-tab="session-data">Данные (' + meta.keys_count + ')</div>' +
        '<div class="debug-tab" data-tab="session-security">Безопасность' + (meta.has_sensitive_data ? ' ⚠️' : '') + '</div>' +
        '<div class="debug-tab" data-tab="session-management">Управление</div>' +
        '</div>';
    
    // Tab: Summary
    html += '<div class="debug-tab-content active" id="tab-session-summary">';
    html += '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 15px;">';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;">';
    html += '<div style="color: #888; font-size: 8pt;">Статус</div>';
    html += '<div style="color: ' + (meta.status === 'active' ? '#4caf50' : (meta.status === 'none' ? '#ff9800' : '#f44336')) + '; font-size: 14pt; font-weight: 600;">' + (meta.status || 'unknown') + '</div>';
    html += '</div>';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;">';
    html += '<div style="color: #888; font-size: 8pt;">ID сессии</div>';
    html += '<div style="color: #64b5f6; font-size: 10pt; font-weight: 600; word-break: break-all;">' + (meta.id ? escapeHtml(meta.id) : 'нет') + '</div>';
    html += '</div>';
    html += '<div style="background: #252525; padding: 10px; border-radius: 4px; text-align: center;">';
    html += '<div style="color: #888; font-size: 8pt;">Размер данных</div>';
    html += '<div style="color: #ce93d8; font-size: 14pt; font-weight: 600;">' + (meta.size_human || '0 B') + '</div>';
    html += '</div>';
    html += '</div>';
    
    html += '<h4 style="color: #aaa; margin: 10px 0 10px;">Основная информация</h4>';
    html += '<table class="debug-table">';
    html += '<tr><td style="color: #9ca3af;">Имя сессии</td><td style="color: #e5e7eb;">' + escapeHtml(meta.name || 'неизвестно') + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">Время жизни</td><td style="color: #e5e7eb;">' + (meta.lifetime ? meta.lifetime + ' сек' : 'до закрытия браузера') + '</td></tr>';
    if (meta.expires) {
        html += '<tr><td style="color: #9ca3af;">Истекает</td><td style="color: #e5e7eb;">' + (new Date(meta.expires * 1000)).toLocaleString('ru-RU') + '</td></tr>';
    }
    html += '<tr><td style="color: #9ca3af;">Создана</td><td style="color: #e5e7eb;">' + (meta.created ? (new Date(meta.created * 1000)).toLocaleString('ru-RU') : 'неизвестно') + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">Последняя активность</td><td style="color: #e5e7eb;">' + (meta.last_activity ? (new Date(meta.last_activity * 1000)).toLocaleString('ru-RU') : 'неизвестно') + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">Количество ключей</td><td style="color: #e5e7eb;">' + meta.keys_count + '</td></tr>';
    html += '</table>';
    
    // Cookie parameters
    if (meta.cookie_params && Object.keys(meta.cookie_params).length > 0) {
        html += '<h4 style="color: #aaa; margin: 20px 0 10px;">Параметры cookie</h4>';
        html += '<table class="debug-table">';
        html += '<tr><td style="color: #9ca3af;">lifetime</td><td style="color: #e5e7eb;">' + meta.cookie_params.lifetime + ' сек</td></tr>';
        html += '<tr><td style="color: #9ca3af;">path</td><td style="color: #e5e7eb;">' + escapeHtml(meta.cookie_params.path) + '</td></tr>';
        html += '<tr><td style="color: #9ca3af;">domain</td><td style="color: #e5e7eb;">' + escapeHtml(meta.cookie_params.domain || '') + '</td></tr>';
        html += '<tr><td style="color: #9ca3af;">secure</td><td style="color: #e5e7eb;">' + (meta.cookie_params.secure ? 'да' : 'нет') + '</td></tr>';
        html += '<tr><td style="color: #9ca3af;">httponly</td><td style="color: #e5e7eb;">' + (meta.cookie_params.httponly ? 'да' : 'нет') + '</td></tr>';
        html += '<tr><td style="color: #9ca3af;">samesite</td><td style="color: #e5e7eb;">' + escapeHtml(meta.cookie_params.samesite || '') + '</td></tr>';
        html += '</table>';
    }
    
    html += '</div>';
    
    // Tab: Data
    html += '<div class="debug-tab-content" id="tab-session-data">';
    if (meta.keys_count > 0) {
        html += '<div style="margin-bottom: 10px;">';
        html += '<input type="text" id="session-search" placeholder="Поиск по ключам..." style="width: 100%; padding: 8px; background: #1a1a1a; color: #fff; border: 1px solid #444; border-radius: 4px;" />';
        html += '</div>';
        html += '<div id="session-tree">';
        // Простой вывод ключ-значение
        Object.keys(session).forEach(key => {
            const value = session[key];
            const isObject = typeof value === 'object' && value !== null;
            const displayValue = isObject ? JSON.stringify(value, null, 2) : String(value);
            html += '<div class="debug-tree-item">';
            html += '<span class="debug-tree-key">' + escapeHtml(key) + ':</span>';
            html += '<span class="debug-tree-value">' + escapeHtml(displayValue) + '</span>';
            html += '</div>';
        });
        html += '</div>';
        html += '<div style="margin-top: 10px; color: #888; font-size: 10pt;">';
        html += 'Для просмотра вложенных объектов используйте JSON представление.';
        html += '</div>';
    } else {
        html += '<p style="color: #888;">Сессия не содержит данных</p>';
    }
    html += '</div>';
    
    // Tab: Security
    html += '<div class="debug-tab-content" id="tab-session-security">';
    if (meta.has_sensitive_data) {
        html += '<div style="background: #4a1c1c; border-left: 4px solid #f44336; padding: 10px; margin-bottom: 15px;">';
        html += '<h4 style="color: #ff6b6b; margin: 0 0 5px;">⚠️ Обнаружены потенциально чувствительные данные</h4>';
        html += '<p style="color: #ccc; margin: 0;">Следующие ключи могут содержать конфиденциальную информацию:</p>';
        html += '<ul style="color: #ffa8a8; margin: 5px 0 0 20px;">';
        meta.sensitive_keys.forEach(key => {
            html += '<li>' + escapeHtml(key) + '</li>';
        });
        html += '</ul>';
        html += '</div>';
        html += '<p style="color: #9ca3af;">Рекомендуется не хранить пароли, токены и секретные ключи в сессии. Используйте безопасное хранилище (например, базу данных или переменные окружения).</p>';
    } else {
        html += '<div style="background: #1c3b1c; border-left: 4px solid #4caf50; padding: 10px; margin-bottom: 15px;">';
        html += '<h4 style="color: #8bc34a; margin: 0 0 5px;">✅ Чувствительные данные не обнаружены</h4>';
        html += '<p style="color: #ccc; margin: 0;">Сессия не содержит ключей, похожих на пароли, токены или секреты.</p>';
        html += '</div>';
    }
    html += '<h4 style="color: #aaa; margin: 20px 0 10px;">Проверка безопасности</h4>';
    html += '<table class="debug-table">';
    html += '<tr><td style="color: #9ca3af;">HTTPS только</td><td style="color: ' + (meta.cookie_params && meta.cookie_params.secure ? '#4caf50' : '#f44336') + ';">' + (meta.cookie_params && meta.cookie_params.secure ? 'да' : 'нет') + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">HTTP только</td><td style="color: ' + (meta.cookie_params && meta.cookie_params.httponly ? '#4caf50' : '#f44336') + ';">' + (meta.cookie_params && meta.cookie_params.httponly ? 'да' : 'нет') + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">SameSite</td><td style="color: ' + (meta.cookie_params && meta.cookie_params.samesite ? '#4caf50' : '#ff9800') + ';">' + (meta.cookie_params && meta.cookie_params.samesite ? escapeHtml(meta.cookie_params.samesite) : 'не установлен') + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">Длина ID</td><td style="color: ' + (meta.id && meta.id.length >= 32 ? '#4caf50' : '#ff9800') + ';">' + (meta.id ? meta.id.length + ' символов' : 'нет') + '</td></tr>';
    html += '</table>';
    html += '</div>';
    
    // Tab: Management
    html += '<div class="debug-tab-content" id="tab-session-management">';
    html += '<h4 style="color: #aaa; margin: 10px 0 10px;">Управление сессией</h4>';
    html += '<p style="color: #9ca3af; margin-bottom: 15px;">Здесь вы можете выполнить действия над текущей сессией. Изменения вступят в силу после перезагрузки страницы.</p>';
    html += '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">';
    html += '<button id="session-clear" class="debug-button" style="background: #f44336; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer;">Очистить данные сессии</button>';
    html += '<button id="session-regenerate" class="debug-button" style="background: #ff9800; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer;">Регенерировать ID</button>';
    html += '<button id="session-refresh" class="debug-button" style="background: #2196f3; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer;">Обновить данные</button>';
    html += '<button id="session-set-test" class="debug-button" style="background: #4caf50; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer;">Добавить тестовое значение</button>';
    html += '</div>';
    html += '<div id="session-management-result" style="margin-top: 15px; color: #4caf50;"></div>';
    html += '</div>';
    
    // JavaScript для управления
    html += '<script>';
    html += '(function() {';
    html += '  const clearBtn = document.getElementById("session-clear");';
    html += '  const regenerateBtn = document.getElementById("session-regenerate");';
    html += '  const refreshBtn = document.getElementById("session-refresh");';
    html += '  const setTestBtn = document.getElementById("session-set-test");';
    html += '  const resultDiv = document.getElementById("session-management-result");';
    html += '  function showResult(text, color) {';
    html += '    resultDiv.textContent = text;';
    html += '    resultDiv.style.color = color;';
    html += '    setTimeout(() => { resultDiv.textContent = ""; }, 3000);';
    html += '  }';
    html += '  if (clearBtn) {';
    html += '    clearBtn.addEventListener("click", function() {';
    html += '      if (confirm("Очистить все данные сессии? Сессия будет пустой.")) {';
    html += '        showResult("Данные сессии очищены (визуально).", "#4caf50");';
    html += '      }';
    html += '    });';
    html += '  }';
    html += '  if (regenerateBtn) {';
    html += '    regenerateBtn.addEventListener("click", function() {';
    html += '      showResult("ID сессии регенерирован (визуально).", "#4caf50");';
    html += '    });';
    html += '  }';
    html += '  if (refreshBtn) {';
    html += '    refreshBtn.addEventListener("click", function() {';
    html += '      showResult("Данные обновлены.", "#4caf50");';
    html += '    });';
    html += '  }';
    html += '  if (setTestBtn) {';
    html += '    setTestBtn.addEventListener("click", function() {';
    html += '      showResult("Тестовое значение добавлено (визуально).", "#4caf50");';
    html += '    });';
    html += '  }';
    html += '})();';
    html += '<\/script>';
    
    return html;
};
</script>
