<?php

declare(strict_types=1);

/**
 * Blueprint Tab - Вкладка шаблонизатора Blueprint
 */

?>
<script>
debugModules.blueprint = function() {
    const bp = debugData.blueprint || {};
    
    let html = '<div class="debug-tabs">' +
        '<div class="debug-tab active" data-tab="bp-templates">Templates</div>' +
        '<div class="debug-tab" data-tab="bp-errors">Errors</div>' +
        '<div class="debug-tab" data-tab="bp-cache">Cache</div>' +
        '<div class="debug-tab" data-tab="bp-paths">Paths</div>' +
        '</div>';
    
    // Templates tab
    html += '<div class="debug-tab-content active" id="tab-bp-templates">';
    const templates = bp.templates || [];
    if (templates.length > 0) {
        html += '<table class="debug-table"><thead><tr><th>Template</th><th>Compiled Path</th><th>From Cache</th><th>Time (ms)</th></tr></thead><tbody>';
        templates.forEach(t => {
            html += '<tr>' +
                '<td style="color: #4caf50;">' + escapeHtml(t.name) + '</td>' +
                '<td style="color: #9ca3af; font-size: 8pt; max-width: 200px; overflow: hidden; text-overflow: ellipsis;">' + escapeHtml(t.compiled_path || '-') + '</td>' +
                '<td style="color: ' + (t.from_cache ? '#ffc107' : '#4caf50') + ';">' + (t.from_cache ? 'Yes' : 'No') + '</td>' +
                '<td style="color: #9ca3af;">' + (t.time * 1000).toFixed(2) + '</td>' +
                '</tr>';
        });
        html += '</tbody></table>';
    } else {
        html += '<p style="color: #888;">Нет скомпилированных шаблонов</p>';
    }
    html += '</div>';
    
    // Errors tab
    html += '<div class="debug-tab-content" id="tab-bp-errors">';
    const errors = bp.errors || [];
    if (errors.length > 0) {
        errors.forEach(e => {
            html += '<div style="padding: 10px; background: rgba(244, 67, 54, 0.1); border-left: 3px solid #f44336; margin-bottom: 10px;">';
            html += '<div style="color: #f44336; font-weight: 600;">' + escapeHtml(e.template || 'Unknown') + '</div>';
            html += '<div style="color: #e5e7eb; margin: 5px 0;">' + escapeHtml(e.message) + '</div>';
            if (e.compiled_code) {
                html += '<pre style="background: #252525; padding: 8px; border-radius: 3px; font-size: 8pt; color: #ce93d8; max-height: 150px; overflow-y: auto; margin-top: 5px;">' + escapeHtml(e.compiled_code.substring(0, 500)) + (e.compiled_code.length > 500 ? '...' : '') + '</pre>';
            }
            html += '</div>';
        });
    } else {
        html += '<p style="color: #4caf50;">Нет ошибок компиляции</p>';
    }
    html += '</div>';
    
    // Cache tab
    html += '<div class="debug-tab-content" id="tab-bp-cache">';
    const cache = bp.cache || {};
    
    html += '<h4 style="color: #e5e7eb; margin: 0 0 10px;">Кэш Blueprint</h4>';
    html += '<table class="debug-table">';
    html += '<tr><td style="color: #9ca3af;">Кэш включён</td><td style="color: ' + (cache.enabled ? '#4caf50' : '#f44336') + ';">' + (cache.enabled ? 'Да' : 'Нет') + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">Путь кэша</td><td style="color: #e5e7eb; font-size: 8pt;">' + escapeHtml(cache.path || '-') + '</td></tr>';
    html += '<tr><td style="color: #9ca3af;">Файлов в кэше</td><td style="color: #4caf50;">' + (cache.files_count || 0) + '</td></tr>';
    html += '</table>';
    
    const cacheFiles = cache.files || [];
    if (cacheFiles.length > 0) {
        html += '<h4 style="color: #e5e7eb; margin: 15px 0 10px;">Файлы кэша</h4>';
        html += '<table class="debug-table"><thead><tr><th>Имя файла</th><th>Размер</th><th>Изменён</th></tr></thead><tbody>';
        cacheFiles.forEach(f => {
            const size = f.size < 1024 ? f.size + ' B' : (f.size < 1048576 ? (f.size / 1024).toFixed(1) + ' KB' : (f.size / 1048576).toFixed(1) + ' MB');
            const date = new Date(f.modified * 1000).toLocaleString();
            html += '<tr><td style="color: #4caf50; font-size: 8pt;">' + escapeHtml(f.name) + '</td><td style="color: #9ca3af;">' + size + '</td><td style="color: #9ca3af; font-size: 8pt;">' + date + '</td></tr>';
        });
        html += '</tbody></table>';
    } else {
        html += '<p style="color: #888; margin-top: 15px;">Нет файлов в кэше</p>';
    }
    html += '</div>';
    
    // Paths tab
    html += '<div class="debug-tab-content" id="tab-bp-paths">';
    const paths = bp.loader_paths || [];
    if (paths.length > 0) {
        html += '<table class="debug-table"><thead><tr><th>#</th><th>Path</th></tr></thead><tbody>';
        paths.forEach((p, i) => {
            html += '<tr><td style="color: #9ca3af;">' + (i + 1) + '</td><td style="color: #4caf50; font-size: 8pt;">' + escapeHtml(p) + '</td></tr>';
        });
        html += '</tbody></table>';
    } else {
        html += '<p style="color: #888;">Нет путей загрузчика</p>';
    }
    html += '</div>';
    
    return html;
};
</script>
