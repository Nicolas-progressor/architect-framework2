<?php
/**
 * View хлебных крошек (Bootstrap 5)
 * Этот файл может отличаться для разных приложений
 */

use Architect\Helpers\Facades\Helper_Breadcrumbs;

$crumbs = Helper_Breadcrumbs::all();

if(empty($crumbs)):
    return;
endif;

$lastIndex = count($crumbs) - 1;
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <?php foreach($crumbs as $index => $crumb): 
            $isActive = $crumb['active'] ?? ($index === $lastIndex);
            $text = $crumb['title'] ?? $crumb['text'] ?? '';
            $href = $crumb['url'] ?? $crumb['href'] ?? null;
        ?>
        <li class="breadcrumb-item<?php echo $isActive ? ' active' : ''; ?>"<?php echo $isActive ? ' aria-current="page"' : ''; ?>>
            <?php if(!$isActive && !empty($href)): ?>
                <a href="<?php echo htmlspecialchars($href); ?>"><?php echo htmlspecialchars($text); ?></a>
            <?php else: ?>
                <?php echo htmlspecialchars($text); ?>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ol>
</nav>
