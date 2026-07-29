<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card text-bg-primary">
            <div class="card-body">
                <h5 class="card-title">Пользователи</h5>
                <p class="card-text display-6"><?= $stats['users'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-bg-success">
            <div class="card-body">
                <h5 class="card-title">Модули</h5>
                <p class="card-text display-6"><?= $stats['modules'] ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-bg-info">
            <div class="card-body">
                <h5 class="card-title">Версия</h5>
                <p class="card-text display-6"><?= $stats['version'] ?></p>
            </div>
        </div>
    </div>
</div>
