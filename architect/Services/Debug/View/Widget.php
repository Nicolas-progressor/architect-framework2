<?php

declare(strict_types=1);

/**
 * Debug Widget View - Compact debug summary.
 * @var array $data - Data from Debug::getData()
 */
?>
<div id="debug-panel-widget" style="display:none;">
    <div class="debug-summary">
        <strong>Time:</strong> <?php echo number_format($data['total_time'] * 1000, 2); ?> ms |
        <strong>Memory:</strong> <?php echo number_format($data['memory_peak'] / 1024, 2); ?> KB |
        <strong>Logs:</strong> <?php echo count($data['logs']); ?> |
        <strong>Queries:</strong> <?php echo count($data['queries']); ?>
    </div>
    
    <?php if (!empty($data['logs'])): ?>
    <div class="debug-logs">
        <strong>Logs:</strong>
        <ul>
            <?php foreach ($data['logs'] as $log): ?>
            <li style="color:<?php echo $log['category'] === 'error' ? '#ff6b6b' : ($log['category'] === 'warning' ? '#ffd93d' : '#fff'); ?>;">
                [<?php echo number_format($log['time'] * 1000, 2); ?>ms] <?php echo htmlspecialchars($log['message']); ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($data['queries'])): ?>
    <div class="debug-queries">
        <strong>SQL Queries:</strong>
        <ul>
            <?php foreach ($data['queries'] as $query): ?>
            <li style="color:#69f0ae;">
                [<?php echo number_format($query['time'] * 1000, 2); ?>ms] <?php echo htmlspecialchars($query['query']); ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
