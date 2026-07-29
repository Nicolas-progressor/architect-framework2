<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Пользователи</h1>
    <a href="/users/create" class="btn btn-primary">+ Добавить</a>
</div>

<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Имя</th>
            <th>Email</th>
            <th>Роль</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users_list as $user): ?>
        <tr>
            <td><?= $user['id'] ?></td>
            <td><?= htmlspecialchars($user['name']) ?></td>
            <td><?= htmlspecialchars($user['email'] ?? '') ?></td>
            <td><?= htmlspecialchars($user['role'] ?? 'user') ?></td>
            <td>
                <a href="/users/<?= $user['id'] ?>/edit" class="btn btn-sm btn-outline-primary">Ред.</a>
                <form method="POST" action="/users/<?= $user['id'] ?>/delete" style="display:inline">
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить?')">Удал.</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
