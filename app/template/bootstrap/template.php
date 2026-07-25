<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->getTitle() ?: 'Architect Framework' ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6c757d;
            --primary-dark: #495057;
        }
        
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem 0;
        }
        
        .site-footer {
            background: #f8f9fa;
            padding: 1.5rem 0;
            margin-top: auto;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>

<body>
    <!-- Навигация -->
    <?php $this->element('navbar'); ?>
    
    <?php if ($this->getElements() || $this->getRoutedElements()): ?>
    <?php if (isset($this->getElements()['breadcrumbs']) || isset($this->getRoutedElements()['breadcrumbs'])): ?>
    <div class="container py-2">
        <?php $this->element('breadcrumbs'); ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Основной контент -->
    <main class="main-content">
        <div class="container">
            <?= $this->getContent() ?>
        </div>
    </main>

    <!-- Подвал -->
    <footer class="site-footer mt-auto py-4 bg-light border-top">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                <span class="text-muted">
                    <i class="bi bi-copyright me-1"></i>
                    2026 Architect Framework
                </span>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span class="text-muted">
                    Общий шаблон
                </span>
            </div>
        </div>
    </div>
</footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
