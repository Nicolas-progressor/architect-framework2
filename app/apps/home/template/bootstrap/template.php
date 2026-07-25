<?php
use Architect\Helpers\Facades\Helper_Title;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helper_Title::render() ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1;
        }
        footer {
            margin-top: auto;
        }
    </style>
</head>
<body>
    
    <?php echo $this->element('navbar'); ?>
    
    <main class="container py-4">
        <?php echo $this->element('breadcrumbs'); ?>
                
        <?= $this->getContent() ?>

        <?php echo $this->element('card'); ?>
    </main>
    
    <?php echo $this->element('footer'); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
