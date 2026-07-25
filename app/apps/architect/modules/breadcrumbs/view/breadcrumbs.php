<?php if (empty($breadcrumbs)) return; ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <?php $count = count($breadcrumbs); ?>
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
            <?php $isLast = ($index === $count - 1); ?>
            <?php if ($isLast): ?>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($crumb['title']) ?></li>
            <?php else: ?>
                <li class="breadcrumb-item">
                    <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['title']) ?></a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>