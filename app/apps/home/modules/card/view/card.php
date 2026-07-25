<div class="row mb-4">
    <?php foreach ($cards as $card): ?>
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-<?= htmlspecialchars($card['icon']) ?>"></i> 
                    <?= htmlspecialchars($card['title']) ?>
                </h5>
                <p class="card-text"><?= htmlspecialchars($card['description']) ?></p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
