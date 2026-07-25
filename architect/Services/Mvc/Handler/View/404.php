<?php
/**
 * @var string $message Error message
 */
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <h1 class="display-1 text-muted">404</h1>
            <h2 class="mb-4">Page Not Found</h2>
            <p class="text-muted mb-4"><?= htmlspecialchars($message ?? 'The page you are looking for does not exist.') ?></p>
            <a href="/" class="btn btn-primary">
                <i class="bi bi-house me-2"></i>Go Home
            </a>
        </div>
    </div>
</div>
