<?php

declare(strict_types=1);

/**
 * Debug Panel Scripts - JavaScript для дебаг-панели
 */

?>
<script>
(function() {
    const bar = document.getElementById('debug-bar');
    const popup = document.getElementById('debug-popup');
    const popupTitle = document.getElementById('debug-popup-title');
    const popupContent = document.getElementById('debug-popup-content');
    const closeBtn = document.getElementById('debug-popup-close');
    
    let currentModule = null;
    
    // Data from PHP
    const debugData = <?php echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
    
    // Module content generators
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
    
    // Event handlers
    bar.addEventListener('click', function(e) {
        const col = e.target.closest('.debug-col');
        if (!col) return;
        
        const module = col.dataset.module;
        if (!debugModules[module]) return;
        
        currentModule = module;
        popupTitle.textContent = module.charAt(0).toUpperCase() + module.slice(1);
        popupContent.innerHTML = debugModules[module]();
        popup.classList.add('active');
        
        // Setup tabs for modules with tabs
        const modulesWithTabs = ['debug', 'routing', 'blueprint', 'time', 'memory', 'database', 'session', 'logs', 'cache', 'performance'];
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
            const modulesWithTabs = ['debug', 'routing', 'blueprint', 'time', 'memory', 'database', 'session', 'logs', 'cache', 'performance'];
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
})();
</script>
