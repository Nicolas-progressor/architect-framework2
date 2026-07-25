<?php

declare(strict_types=1);

/**
 * Environment Tab - Вкладка окружения
 */

?>
<script>
debugModules.environment = function() {
    const env = debugData.environment;
    
    return '<div class="debug-env-list">' +
        '<div class="debug-env-item"><span class="debug-env-key">APP_ENV</span><span class="debug-env-value">' + escapeHtml(env) + '</span></div>' +
        '<div class="debug-env-item"><span class="debug-env-key">PHP Version</span><span class="debug-env-value"><?= PHP_VERSION ?></span></div>' +
        '<div class="debug-env-item"><span class="debug-env-key">Memory Limit</span><span class="debug-env-value"><?= ini_get("memory_limit") ?></span></div>' +
        '<div class="debug-env-item"><span class="debug-env-key">Max Execution Time</span><span class="debug-env-value"><?= ini_get("max_execution_time") ?>s</span></div>' +
        '</div>';
};
</script>
