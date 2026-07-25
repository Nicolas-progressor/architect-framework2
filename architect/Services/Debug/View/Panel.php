<?php

declare(strict_types=1);

/**
 * Debug Panel View - Interactive debug panel at the bottom of the screen.
 * @var array $data - Data from Debug::getData()
 *
 * Структура:
 * - partials/Styles.php   - CSS стили
 * - partials/Bar.php      - Верхняя панель (debug-bar)
 * - partials/Popup.php    - Popup окно
 * - partials/Scripts.php  - JavaScript
 * - tabs/*.php           - Контент каждой вкладки
 */

// Форматирование памяти
if (!function_exists('formatMemory')) {
    function formatMemory(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        } else {
            return round($bytes / 1048576, 1) . ' MB';
        }
    }
}

$timeMs = round($data['total_time'] * 1000, 1);
$totalLogs = count($data['logs']) + ($data['collector']['total_messages'] ?? 0) + count($data['system_logs'] ?? []);
$logIssues = $totalLogs;
$collector = $data['collector'] ?? null;
$hasCustomData = $data['has_custom_data'] ?? false;
$debugInfo = '';

if ($hasCustomData && $collector) {
    $debugInfo = $collector['category_count'] . ' categories, ' . $collector['total_messages'] . ' messages';
}

$tabsDir = __DIR__ . '/tabs/';
$tabFiles = [
    'TimeTab.php',
    'MemoryTab.php',
    'DatabaseTab.php',
    'LogsTab.php',
    'CacheTab.php',
    'SessionTab.php',
    'EnvironmentTab.php',
    'RoutingTab.php',
    'DebugTab.php',
    'BlueprintTab.php',
    'PerformanceTab.php',
];
?>
<!-- DEBUG PANEL START -->
<?php include_once __DIR__ . '/partials/Styles.php'; ?>
<?php include_once __DIR__ . '/partials/Bar.php'; ?>
<?php include_once __DIR__ . '/partials/Popup.php'; ?>

<script>
    // Global debug data passed from PHP
    const debugData = <?php echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const debugModules = {};

    // Helper functions
    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }
    
    function escapeHtml(str) {
        if (typeof str !== 'string') return str;
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    // Tabs setup
    function setupTabs() {
        const tabs = document.querySelectorAll('.debug-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.dataset.tab;
                
                document.querySelectorAll('.debug-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.debug-tab-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById('tab-' + tabId).classList.add('active');
                
                // Apply log level filter if function exists
                if (typeof filterLogsTable === 'function') {
                    setTimeout(filterLogsTable, 10);
                }
            });
        });
        
        // Route file click handler
        const routeFileRows = document.querySelectorAll('.route-file-row');
        routeFileRows.forEach(row => {
            row.addEventListener('click', function() {
                const idx = parseInt(this.dataset.index);
                const contentDiv = document.getElementById('route-file-content');
                const contentText = document.getElementById('route-file-content-text');
                
                const fileData = debugData.routing?.route_files?.[idx];
                if (fileData && fileData.content) {
                    contentText.textContent = JSON.stringify(fileData.content, null, 2);
                } else {
                    contentText.textContent = 'Нет данных';
                }
                contentDiv.style.display = 'block';
            });
        });
    }
    function initDebugPanel() {
        const bar = document.getElementById('debug-bar');
        const popup = document.getElementById('debug-popup');
        const popupTitle = document.getElementById('debug-popup-title');
        const popupContent = document.getElementById('debug-popup-content');
        const closeBtn = document.getElementById('debug-popup-close');
        
        if (!bar || !popup || !popupTitle || !popupContent || !closeBtn) {
            // console.error('Debug panel elements missing, retrying...');
            setTimeout(initDebugPanel, 100);
            return;
        }
        
        let currentModule = null;
        
    
    
    // Event handlers
    if (!bar) {
        // console.error('Debug bar element not found');
    }
    if (!popup) {
        // console.error('Debug popup element not found');
    }
    if (!popupTitle || !popupContent || !closeBtn) {
        // console.error('One of popup sub-elements not found');
    }
    
    bar.addEventListener('click', function(e) {
        // console.log('Debug bar clicked', e.target);
        const col = e.target.closest('.debug-col');
        // console.log('Closest debug-col:', col);
        if (!col) {
            // console.log('No debug-col found');
            return;
        }
        
        const module = col.dataset.module;
        // console.log('Module:', module, 'debugModules[module]:', debugModules[module]);
        if (!debugModules[module]) {
            // console.warn('Module not found in debugModules:', module);
            return;
        }
        
        currentModule = module;
        popupTitle.textContent = module.charAt(0).toUpperCase() + module.slice(1);
        popupContent.innerHTML = debugModules[module]();
        popup.classList.add('active');
        // console.log('Popup opened for module:', module, 'popup classList:', popup.classList);
        
        // Setup tabs for modules with tabs
        const modulesWithTabs = ['debug', 'routing', 'blueprint', 'time', 'database', 'logs', 'cache', 'session', 'memory', 'performance'];
        if (modulesWithTabs.includes(module)) {
            setupTabs();
        }
    });
    
    closeBtn.addEventListener('click', function() {
        popup.classList.remove('active');
        currentModule = null;
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && popup.classList.contains('active')) {
            popup.classList.remove('active');
            currentModule = null;
        }
    });
    
    popup.addEventListener('click', function(e) {
        if (e.target === popup) {
            popup.classList.remove('active');
            currentModule = null;
        }
    });
    
    // Refresh button
    document.getElementById('debug-refresh').addEventListener('click', function() {
        if (currentModule && debugModules[currentModule]) {
            popupContent.innerHTML = debugModules[currentModule]();
            const modulesWithTabs = ['debug', 'routing', 'blueprint', 'time', 'database', 'logs', 'cache', 'session', 'memory', 'performance'];
            if (modulesWithTabs.includes(currentModule)) {
                setupTabs();
            }
        }
    });
    
    // Export button
    document.getElementById('debug-export').addEventListener('click', function() {
        const dataStr = JSON.stringify(debugData, null, 2);
        const blob = new Blob([dataStr], {type: 'application/json'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'debug-' + Date.now() + '.json';
        a.click();
        URL.revokeObjectURL(url);
    });
    
    // Clear button
    document.getElementById('debug-clear').addEventListener('click', function() {
        if (confirm('Очистить все данные отладки?')) {
            location.reload();
        }
    });

    }
    initDebugPanel();
</script>

<?php
// Подключение табов
foreach ($tabFiles as $tabFile) {
    if (file_exists($tabsDir . $tabFile)) {
        include_once $tabsDir . $tabFile;
    }
}
?>
<!-- DEBUG PANEL END -->
