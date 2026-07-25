<?php

declare(strict_types=1);

/**
 * Routing Tab - Вкладка маршрутизации
 */

?>
<script>
debugModules.routing = function() {
    const routing = debugData.routing || {};
    const current = routing.current || {};
    
    let html = '<div class="debug-tabs">' +
        '<div class="debug-tab active" data-tab="current-route">Current Route</div>' +
        '<div class="debug-tab" data-tab="route-rules">Route Rules</div>' +
        '<div class="debug-tab" data-tab="route-file">Route File</div>' +
        '</div>';
    
    // Current Route tab
    html += '<div class="debug-tab-content active" id="tab-current-route">';
    html += '<table class="debug-table">';
    html += '<tr><td>Path</td><td style="color: #4caf50;">' + escapeHtml(current.path || '/') + '</td></tr>';
    html += '<tr><td>Module</td><td style="color: #ce93d8;">' + escapeHtml(current.module || '-') + '</td></tr>';
    html += '<tr><td>Controller</td><td style="color: #ce93d8;">' + escapeHtml(current.controller || '-') + '</td></tr>';
    html += '<tr><td>Action</td><td style="color: #ce93d8;">' + escapeHtml(current.action || '-') + '</td></tr>';
    
    if (current.segments && current.segments.length > 0) {
        html += '<tr><td>Segments</td><td style="color: #e5e7eb;">[' + current.segments.map(s => escapeHtml(s)).join(', ') + ']</td></tr>';
    }
    
    if (current.params && Object.keys(current.params).length > 0) {
        for (const [key, value] of Object.entries(current.params)) {
            html += '<tr><td>Param: ' + escapeHtml(key) + '</td><td style="color: #e5e7eb;">' + escapeHtml(String(value)) + '</td></tr>';
        }
    }
    html += '</table></div>';
    
    // Route Rules tab
    html += '<div class="debug-tab-content" id="tab-route-rules">';
    const routes = routing.routes || {};
    if (Object.keys(routes).length > 0) {
        html += '<table class="debug-table"><thead><tr><th>Route</th><th>Module</th><th>Controller</th><th>Action</th></tr></thead><tbody>';
        for (const [key, route] of Object.entries(routes)) {
            html += '<tr><td style="color: #e5e7eb;">' + escapeHtml(key) + '</td><td style="color: #9ca3af;">' + escapeHtml(route.module || '-') + '</td><td style="color: #9ca3af;">' + escapeHtml(route.controller || '-') + '</td><td style="color: #9ca3af;">' + escapeHtml(route.action || '-') + '</td></tr>';
        }
        html += '</tbody></table>';
    } else {
        html += '<p style="color: #888;">Нет правил маршрутов</p>';
    }
    html += '</div>';
    
    // Route Files tab
    html += '<div class="debug-tab-content" id="tab-route-file">';
    const routeFiles = routing.route_files || [];
    if (routeFiles.length > 0) {
        html += '<table class="debug-table"><thead><tr><th>Type</th><th>File</th></tr></thead><tbody>';
        routeFiles.forEach((f, idx) => {
            html += '<tr class="route-file-row" data-index="' + idx + '">' +
                '<td style="color: #9ca3af;">' + escapeHtml(f.type || '-') + '</td>' +
                '<td style="color: #4caf50; cursor: pointer;">' + escapeHtml(f.name || f.path) + '</td>' +
                '</tr>';
        });
        html += '</tbody></table>';
        html += '<div id="route-file-content" style="display: none; margin-top: 15px; padding: 10px; background: #252525; border-radius: 4px;">';
        html += '<div style="color: #aaa; font-size: 8pt; margin-bottom: 5px;">Содержимое:</div>';
        html += '<pre id="route-file-content-text" style="color: #ce93d8; font-size: 8pt; white-space: pre-wrap; max-height: 300px; overflow-y: auto;"></pre>';
        html += '</div>';
    } else {
        html += '<p style="color: #888;">Нет файлов маршрутов</p>';
    }
    html += '</div>';
    
    return html;
};
</script>
