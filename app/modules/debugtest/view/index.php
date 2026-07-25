<h1><?php echo htmlspecialchars($title ?? 'Debug Test'); ?></h1>
<p><?php echo htmlspecialchars($message ?? ''); ?></p>

<h2>Таймеры</h2>
<ul>
<?php foreach ($timers ?? [] as $name => $value): ?>
    <li><strong><?php echo htmlspecialchars($name); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
<?php endforeach; ?>
</ul>

<p><em>Откройте Debug панель внизу экрана для просмотра всех данных</em></p>
