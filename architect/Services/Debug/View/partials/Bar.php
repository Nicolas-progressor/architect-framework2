<?php

declare(strict_types=1);

/**
 * Debug Bar - Верхняя панель с иконками
 * @var array $data - Data from Debug::getData()
 */

$timeMs = round($data['total_time'] * 1000, 1);
$totalLogs = count($data['logs']) + (isset($data['collector']['total_messages']) ? $data['collector']['total_messages'] : 0) + count($data['system_logs'] ?? []);
$logIssues = $totalLogs;
$collector = $data['collector'] ?? null;
$hasCustomData = $data['has_custom_data'] ?? false;
$debugInfo = '';

if ($hasCustomData && $collector) {
    $debugInfo = $collector['category_count'] . ' categories, ' . $collector['total_messages'] . ' messages';
}

?>
<div id="debug-bar">
    <!-- Time -->
    <div class="debug-col" data-module="time" data-color="<?= $data['time_color'] ?>" title="Время выполнения запроса">
        <span class="icon">⏱️</span>
        <span class="value"><?= $timeMs ?> ms</span>
    </div>
    
    <!-- Memory -->
    <?php $memoryMb = formatMemory($data['memory_peak']); ?>
    <div class="debug-col" data-module="memory" data-color="<?= $data['memory_color'] ?>" title="Использование памяти">
        <span class="icon">💾</span>
        <span class="value"><?= $memoryMb ?></span>
    </div>
    
    <!-- Routing -->
    <div class="debug-col" data-module="routing" data-color="blue" title="Маршрутизация">
        <span class="icon">🔀</span>
        <span class="value"><?= htmlspecialchars($data['routing']['current']['controller'] ?? '-') ?>/<?= htmlspecialchars($data['routing']['current']['action'] ?? '-') ?></span>
    </div>
    
    <!-- Database -->
    <div class="debug-col" data-module="database" data-color="<?= $data['has_slow_queries'] ? 'red' : 'gray' ?>" title="SQL-запросы">
        <span class="icon">🗄️</span>
        <span class="value"><?= $data['query_count'] ?></span>
    </div>
    
    <!-- Logs -->
    <div class="debug-col" data-module="logs" data-color="<?= $data['error_count'] > 0 ? 'red' : ($data['warning_count'] > 0 ? 'yellow' : 'gray') ?>" title="Ошибки и предупреждения">
        <span class="icon">⚠️</span>
        <span class="value"><?= $logIssues ?></span>
    </div>
    
    <!-- Cache -->
    <div class="debug-col" data-module="cache" data-color="<?= $data['cache_color'] ?>" title="Статистика кеша">
        <span class="icon">🔁</span>
        <span class="value">hit:<?= $data['cache_hits'] ?>/miss:<?= $data['cache_misses'] ?></span>
    </div>
    
    <!-- Session -->
    <?php
    $sessionMeta = $data['session_meta'] ?? [];
    $sessionStatus = $sessionMeta['status'] ?? 'unknown';
    $sessionId = $sessionMeta['id'] ?? '';
    $sessionCount = $data['session_count'];
    $sessionColor = match ($sessionStatus) {
        'active' => 'green',
        'none' => 'gray',
        'disabled' => 'orange',
        default => 'blue',
    };
    $sessionTitle = 'Сессия: статус ' . $sessionStatus;
    if ($sessionId) {
        $sessionTitle .= ', ID: ' . substr($sessionId, 0, 8) . '...';
    }
    $sessionTitle .= ', данных: ' . $sessionCount;
    ?>
    <div class="debug-col" data-module="session" data-color="<?= $sessionColor ?>" title="<?= htmlspecialchars($sessionTitle) ?>">
        <span class="icon">🖐️</span>
        <span class="value"><?= $sessionCount ?></span>
    </div>
    
    <!-- Environment -->
    <?php $env = $data['environment']; ?>
    <div class="debug-col" data-module="environment" data-color="<?= $data['env_color'] ?>" title="Текущее окружение">
        <span class="icon">⚙️</span>
        <span class="value"><?= htmlspecialchars($env) ?></span>
    </div>
    
    <!-- Debug (Custom Data) -->
    <?php if ($hasCustomData): ?>
    <div class="debug-col" data-module="debug" data-color="<?= ($collector['level_stats']['error'] ?? 0) > 0 ? 'red' : 'blue' ?>" title="Пользовательские данные отладки">
        <span class="icon">🛠️</span>
        <span class="value"><?= htmlspecialchars($debugInfo) ?></span>
    </div>
    <?php endif; ?>
    
    <!-- Blueprint -->
    <?php if ($data['has_blueprint'] ?? false):
        $bp = $data['blueprint'] ?? [];
        $bpErrors = count($bp['errors'] ?? []);
        $bpTemplates = count($bp['templates'] ?? []);
        $bpColor = $bpErrors > 0 ? 'red' : 'blue';
    ?>
    <div class="debug-col" data-module="blueprint" data-color="<?= $bpColor ?>" title="Blueprint шаблонизатор">
        <span class="icon">📝</span>
        <span class="value"><?= $bpTemplates ?> tmpl<?= $bpErrors > 0 ? ', ' . $bpErrors . ' err' : '' ?></span>
    </div>
    <?php endif; ?>
    
    <!-- Performance -->
    <?php if ($data['has_performance'] ?? false):
        $performance = $data['performance'] ?? [];
        $profiler = $data['profiler'] ?? [];
        $alerts = $performance['alerts'] ?? [];
        $alertCount = count($alerts);
        $perfColor = $alertCount > 0 ? 'red' : 'green';
        $stageCount = count($performance['stage_timings'] ?? []);
        $profilerSegments = count($profiler['segments'] ?? []);
        $title = "Производительность: этапов $stageCount, сегментов $profilerSegments";
        if ($alertCount > 0) {
            $title .= ", алертов $alertCount";
        }
    ?>
    <div class="debug-col" data-module="performance" data-color="<?= $perfColor ?>" title="<?= htmlspecialchars($title) ?>">
        <span class="icon">🚀</span>
        <span class="value"><?= $alertCount > 0 ? '⚠️' : '✓' ?> perf</span>
    </div>
    <?php endif; ?>
</div>
