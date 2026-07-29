<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2"><?= $is_edit ? 'Редактировать' : 'Создать' ?> пользователя</h1>
    <a href="/users" class="btn btn-outline-secondary">← Назад</a>
</div>

<form method="POST">
    <div class="mb-3">
        <label class="form-label">Имя</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Роль</label>
        <select name="role" class="form-select">
            <option value="user" <?= ($user['role'] ?? '') === 'user' ? 'selected' : '' ?>>User</option>
            <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Сохранить</button>
</form>
