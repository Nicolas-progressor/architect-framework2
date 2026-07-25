# Аутентификация и авторизация (Auth)

Компонент Auth предоставляет систему аутентификации (проверка подлинности пользователя) и авторизации (проверка прав доступа) на основе ролей и разрешений (RBAC). Он включает middleware, модели пользователей, ролей и разрешений, а также хелперы для удобной работы в контроллерах и шаблонах.

## Установка

Модуль аутентификации устанавливается отдельно:

```bash
composer require architect/auth-system
```

После установки необходимо выполнить миграции для создания таблиц пользователей, ролей и разрешений.

## Конфигурация

Основные настройки находятся в `app/config/auth.json`:

```json
{
    "driver": "session",
    "model": "Architect\\AuthSystem\\Models\\User",
    "table": "users",
    "primary_key": "id",
    "password_hash": "password_hash",
    "remember_token": "remember_token",
    "login_route": "/login",
    "logout_route": "/logout",
    "redirect_after_login": "/dashboard",
    "redirect_after_logout": "/",
    "guards": {
        "web": {
            "driver": "session",
            "provider": "users"
        },
        "api": {
            "driver": "token",
            "provider": "users"
        }
    }
}
```

- `driver` – драйвер хранения сессии (`session`, `token`).
- `model` – класс модели пользователя.
- `table` – таблица пользователей.
- `primary_key` – первичный ключ.
- `password_hash` – поле хеша пароля.
- `remember_token` – поле токена "запомнить меня".
- `guards` – охранники (guards) для разных типов аутентификации.

## Модели

### User

Модель пользователя расширяет `Architect\AuthSystem\Models\User` и использует трейты для работы с ролями и разрешениями.

```php
namespace App\Modules\Auth\Models;

use Architect\AuthSystem\Models\User as BaseUser;

class User extends BaseUser
{
    // дополнительные поля и методы
}
```

### Role и Permission

Модели ролей и разрешений находятся в `Architect\AuthSystem\Models\Role` и `Architect\AuthSystem\Models\Permission`. Они связаны отношением "многие ко многим".

## Аутентификация

### Вход пользователя

```php
use Architect\AuthSystem\Helpers\Auth;

if (Auth::attempt(['email' => $email, 'password' => $password])) {
    // успешный вход
}
```

С опцией "запомнить меня":

```php
Auth::attempt($credentials, true);
```

### Выход

```php
Auth::logout();
```

### Проверка аутентификации

```php
if (Auth::check()) {
    $user = Auth::user();
}
```

### Получение текущего пользователя

```php
$user = Auth::user();
```

Если пользователь не аутентифицирован, возвращается `null`.

## Авторизация (RBAC)

### Роли

Роли – это группы пользователей (например, `admin`, `moderator`, `user`). Пользователю может быть назначено несколько ролей.

```php
$user->assignRole('admin');
$user->hasRole('admin'); // true
$user->removeRole('admin');
```

### Разрешения

Разрешения – это конкретные действия (например, `create_post`, `edit_user`). Разрешения могут быть привязаны к ролям или непосредственно к пользователям.

```php
$role->givePermission('edit_user');
$user->givePermission('delete_post');
```

### Проверка разрешений

```php
if (Auth::can('edit_user')) {
    // действие разрешено
}
```

Проверка через модель пользователя:

```php
$user->can('edit_user');
```

### Middleware авторизации

Маршруты могут быть защищены middleware `auth`, `role`, `permission`.

```json
{
    "route": "/admin",
    "controller": "AdminController",
    "middleware": ["auth", "role:admin"]
}
```

```json
{
    "route": "/posts/{id}/edit",
    "controller": "PostController",
    "middleware": ["auth", "permission:edit_post"]
}
```

## Сессии и токены

### Сессионная аутентификация

По умолчанию используется драйвер `session`. Идентификатор пользователя хранится в сессии PHP.

### Токеновая аутентификация (API)

Для API можно использовать драйвер `token`. Пользователь передаёт токен в заголовке `Authorization: Bearer <token>`.

## События аутентификации

Компонент генерирует события, на которые можно подписаться:

- `auth.login` – успешный вход.
- `auth.logout` – выход.
- `auth.failed` – неудачная попытка входа.
- `auth.registered` – регистрация нового пользователя.

Пример подписки:

```php
use Architect\AuthSystem\Events\LoginEvent;

Event::listen(LoginEvent::class, function($event) {
    $user = $event->user;
    $ip = $event->ip;
    // логирование
});
```

## Интеграция с контроллерами

### Контроллер аутентификации

Architect предоставляет готовый контроллер `Architect\AuthSystem\Controllers\AuthController`, который можно использовать для регистрации, входа, выхода и восстановления пароля. Вы можете расширить его или создать собственный.

### Защита контроллеров

Используйте трейт `AuthorizesRequests` для проверки прав внутри методов:

```php
use Architect\AuthSystem\Traits\AuthorizesRequests;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function edit($id)
    {
        $this->authorize('edit_post');
        // ...
    }
}
```

## Хелперы

### Глобальные функции

- `auth()` – возвращает экземпляр AuthManager.
- `user()` – возвращает текущего пользователя (или null).
- `can($permission)` – проверяет разрешение для текущего пользователя.

### В шаблонах

В PHP-шаблонах:

```php
<?php if (auth()->check()): ?>
    Привет, <?= auth()->user()->name ?>
<?php endif; ?>
```

В Blueprint:

```blade
@auth
    <p>Welcome, {{ auth().user().name }}</p>
@endauth

@can('edit_post')
    <a href="/edit">Edit</a>
@endcan
```

## Кастомизация

### Собственный провайдер пользователей

Вы можете реализовать собственный UserProvider, если пользователи хранятся не в БД, а в LDAP, внешнем API и т.д.

```php
use Architect\AuthSystem\Contracts\UserProviderInterface;

class CustomUserProvider implements UserProviderInterface
{
    // реализация методов
}
```

Зарегистрируйте его в контейнере под ключом `auth.provider`.

### Собственный guard

Для нестандартных сценариев аутентификации (например, JWT) можно создать собственный guard, реализовав `GuardInterface`.

## Примеры

### Регистрация пользователя

```php
use Architect\AuthSystem\Helpers\Auth;
use App\Modules\Auth\Models\User;

$user = new User();
$user->name = $request->input('name');
$user->email = $request->input('email');
$user->password = password_hash($request->input('password'), PASSWORD_DEFAULT);
$user->save();

Auth::login($user);
```

### Проверка роли в middleware

```php
public function handle($request, $next, $role)
{
    if (!Auth::check() || !Auth::user()->hasRole($role)) {
        return redirect('/login');
    }
    return $next($request);
}
```

## Заключение

Компонент Auth предоставляет полнофункциональную систему аутентификации и авторизации, соответствующую современным стандартам безопасности. Его интеграция с middleware, событиями и RBAC позволяет гибко управлять доступом в приложении.

Дополнительные сведения см. в [документации по аутентификации](../docs2/auth.md).