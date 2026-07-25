<?php

declare(strict_types=1);

/**
 * Debug Popup - Всплывающая панель
 */

?>
<!-- Popup Panel -->
<div id="debug-popup">
    <div class="debug-popup-header">
        <span class="debug-popup-title" id="debug-popup-title">Debug</span>
        <span class="debug-popup-close" id="debug-popup-close">×</span>
    </div>
    <div class="debug-popup-toolbar">
        <button class="debug-popup-btn" id="debug-refresh">Обновить</button>
        <button class="debug-popup-btn" id="debug-export">Экспорт</button>
        <button class="debug-popup-btn" id="debug-clear">Очистить</button>
    </div>
    <div class="debug-popup-content" id="debug-popup-content">
        <!-- Content loaded via JavaScript -->
    </div>
</div>
