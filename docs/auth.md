# Аутентификация и авторизация

Architect Framework включает полнофункциональную систему аутентификации и авторизации (Auth System), построенную на основе ролей и разрешений (RBAC). Система поддерживает аутентификацию через сессии, JWT-токены, интеграцию с базой данных, кастомные провайдеры и middleware для защиты маршрутов.

## Содержание

- [Введение](#введение)
- [Конфигурация](#конфигурация)
  - [Файл auth.json](#файл-authjson)
  - [Роли и разрешения](#роли-и-разрешения)
  - [Настройки приложения](#настройки-приложения)
- [Базовое использование](#базовое-использование)
  - [Получение сервиса Auth](#получение-сервиса-auth)
  - [Вход и выход](#вход-и-выход)
  - [Проверка аутентификации](#проверка-аутентификации)
  - [Проверка ролей и разрешений](#проверка-ролей-и-разрешений)
- [Регистрация пользователей](#регистрация-пользователей)
- [JWT-аутентификация](#jwt-аутентификация)
- [Middleware](#middleware)
  - [AuthMiddleware](#authmiddleware)
  - [GuestMiddleware](#guestmiddleware)
  - [RoleMiddleware](#rolemiddleware)
- [Интеграция с MVC](#интеграция-с-mvc)
- [Кастомизация](#кастомизация)
  - [Собственные провайдеры](#собственные-провайдеры)
  - [Собственные модели](#собственные-модели)
- [Лучшие практики](#лучшие-практики)
- [Частые вопросы](#частые-вопросы)

## Введение

Система аутентификации Architect предоставляет:

- **Аутентификацию по логину/паролю** с поддержкой сессий.
- **JWT-токены** для API-аутентификации.
- **Ролевую модель (RBAC)** – роли и разрешения, назначаемые пользователям.
- **Middleware** для защиты маршрутов.
- **Гибкую конфигурацию** через JSON-файлы.
- **Интеграцию с базой данных** через модели User, Role, Permission.
- **События и логирование** – логирование попыток входа, регистрации и т.д.

Система состоит из нескольких компонентов:

- `AuthManager` – основной сервис управления аутентификацией.
- `AuthMiddleware` – middleware для проверки аутентификации.
- `RoleMiddleware` – middleware для проверки ролей.
- `GuestMiddleware` – middleware для доступа только гостей.
- Модели `User`, `Role`, `Permission` – сущности базы данных.

## Конфигурация

### Файл auth.json

Основные настройки аутентификации хранятся в `app/config/auth.json`:

```json
{
    "driver": "database",
    "table_prefix": "auth_",
    "session_lifetime": 1440,
    "password_hash_algorithm": "bcrypt",
    "password_cost": 12,
    "jwt_secret": "change-me-in-production",
    "jwt_ttl": 3600,
    "default_role": "guest",
    "urls": {
        "login": "/login",
        "logout": "/logout",
        "register": "/register",
        "redirect_after_login": "/",
        "redirect_after_logout": "/",
        "redirect_after_register": "/",
        "password_reset": "/password-reset",
        "email_verification": "/email-verify"
    },
    "roles": {
        "admin": ["*"],
        "moderator": ["post.create", "post.edit", "post.delete"],
        "user": ["post.create", "post.edit"],
        "guest": []
    },
    "permissions": {
        "post.create": "Create posts",
        "post.edit": "Edit posts",
        "post.delete": "Delete posts",
        "user.manage": "Manage users"
    }
}
```

**Опции:**

- `driver` – драйвер аутентификации (`database`, `ldap`, `custom`).
- `table_prefix` – префикс таблиц в базе данных.
- `session_lifetime` – время жизни сессии в минутах.
- `password_hash_algorithm` – алгоритм хеширования пароля (`bcrypt`, `argon2i`, `argon2id`).
- `password_cost` – стоимость хеширования (для bcrypt).
- `jwt_secret` – секретный ключ для подписи JWT-токенов.
- `jwt_ttl` – время жизни JWT-токена в секундах.
- `default_role` – роль, назначаемая новым пользователям по умолчанию.
- `urls` – URL-адреса для страниц аутентификации.
- `roles` – определение ролей и их разрешений (`*` – все разрешения).
- `permissions` – описание разрешений (для справки).

### Роли и разрешения

Роли определяются как ключ-значение, где ключ – имя роли, значение – массив разрешений. Специальное разрешение `*` означает все разрешения.

Пример:

```json
"roles": {
    "admin": ["*"],
    "moderator": ["post.create", "post.edit", "post.delete"],
    "user": ["post.create", "post.edit"],
    "guest": []
}
```

Разрешения могут быть любыми строками, рекомендуется использовать точечную нотацию (`module.action`).

### Настройки приложения

Каждое приложение может иметь собственный файл `auth.json` в своей директории (`app/apps/{app}/config/auth.json`), который переопределяет глобальные настройки. Это позволяет иметь разные роли и разрешения для разных приложений.

## Базовое использование

### Получение сервиса Auth

Сервис Auth доступен через контейнер зависимостей:

```php
$auth = $this->container->get('auth');
```

Или в контроллере через свойство (если подключен трейт):

```php
$user = $this->auth->getUser();
```

### Вход и выход

**Аутентификация по логину и паролю:**

```php
if ($this->auth->login($username, $password)) {
    // Успешный вход
    $redirectUrl = $this->auth->getRedirectAfterLogin();
    return $this->redirect($redirectUrl);
} else {
    // Ошибка аутентификации
    $this->flash->error('Неверный логин или пароль');
    return $this->redirect($this->auth->getLoginUrl());
}
```

**Вход по объекту пользователя (например, после регистрации):**

```php
$user = User::find(1);
$this->auth->loginUser($user);
```

**Выход:**

```php
$this->auth->logout();
return $this->redirect($this->auth->getRedirectAfterLogout());
```

### Проверка аутентификации

```php
if ($this->auth->isLoggedIn()) {
    $user = $this->auth->getUser();
    echo 'Привет, ' . $user->username;
} else {
    echo 'Вы не авторизованы';
}
```

**Получение ID пользователя:**

```php
$userId = $this->auth->getUserId();
```

### Проверка ролей и разрешений

**Проверка роли:**

```php
if ($this->auth->hasRole('admin')) {
    // Пользователь является администратором
}
```

**Проверка разрешения:**

```php
if ($this->auth->hasPermission('post.create')) {
    // Пользователь может создавать посты
}
```

**Проверка на администратора (удобный метод):**

```php
if ($this->auth->isAdmin()) {
    // Пользователь администратор
}
```

## Регистрация пользователей

```php
$user = $this->auth->register(
    username: 'john_doe',
    email: 'john@example.com',
    password: 'secret123',
    role: 'user' // опционально, по умолчанию используется default_role
);

if ($user) {
    // Автоматически входим после регистрации
    $this->auth->loginUser($user);
    return $this->redirect($this->auth->getRedirectAfterRegister());
} else {
    // Ошибка (username или email уже заняты)
    $this->flash->error('Пользователь с таким именем или email уже существует');
    return $this->redirect($this->auth->getRegisterUrl());
}
```

## JWT-аутентификация

JWT-аутентификация используется для API-запросов. При успешном входе через сессию генерируется JWT-токен, который можно использовать для аутентификации в заголовке `Authorization: Bearer <token>`.

**Получение JWT-токена текущего пользователя:**

```php
$token = $this->auth->getJWT();
```

**Верификация JWT-токена:**

```php
$payload = $this->auth->verifyJWT($token);
if ($payload) {
    $userId = $payload['sub'];
    $user = User::find($userId);
}
```

**Использование JWT в API-маршрутах:**

Добавьте middleware `JwtMiddleware` (если предусмотрен) или проверяйте токен вручную.

## Middleware

### AuthMiddleware

Проверяет, аутентифицирован ли пользователь. Если нет – перенаправляет на страницу входа.

**Настройка в маршруте:**

```json
{
    "route": "/dashboard",
    "controller": "DashboardController",
    "action": "index",
    "middleware": ["auth"]
}
```

**Использование в контроллере:**

```php
use Architect\Auth\Middleware\AuthMiddleware;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(AuthMiddleware::class);
    }
}
```

### GuestMiddleware

Разрешает доступ только неаутентифицированным пользователям (гостям). Если пользователь уже вошёл, перенаправляет на главную.

```json
{
    "route": "/login",
    "controller": "AuthController",
    "action": "login",
    "middleware": ["guest"]
}
```

### RoleMiddleware

Проверяет, имеет ли пользователь определённую роль. Если нет – возвращает 403.

```json
{
    "route": "/admin",
    "controller": "AdminController",
    "action": "index",
    "middleware": ["role:admin"]
}
```

Можно указать несколько ролей через запятую: `"role:admin,moderator"`.

**Использование в коде:**

```php
use Architect\Auth\Middleware\RoleMiddleware;

$this->middleware(RoleMiddleware::class, ['role' => 'admin']);
```

## Интеграция с MVC

Система аутентификации тесно интегрирована с MVC-архитектурой:

- **Контроллеры** – могут использовать трейт `AuthTrait` для быстрого доступа к методам аутентификации.
- **Модели** – модель `User` расширяет `ModelBase` и включает методы для работы с ролями и разрешениями.
- **Представления** – доступны хелперы для проверки аутентификации в шаблонах.

**Пример контроллера с аутентификацией:**

```php
<?php
namespace Modules\Auth;

use Architect\Services\Mvc\Controller;
use Architect\Auth\Middleware\AuthMiddleware;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(AuthMiddleware::class);
    }
    
    public function index()
    {
        $user = $this->auth->getUser();
        return $this->view('dashboard/index', ['user' => $user]);
    }
}
```

## Кастомизация

### Собственные провайдеры

Вы можете создать собственный провайдер аутентификации, реализовав интерфейс `AuthProviderInterface`, и указать его в конфигурации как `driver: custom`.

```php
<?php
namespace App\Auth;

use Architect\Auth\Contracts\AuthProviderInterface;

class CustomAuthProvider implements AuthProviderInterface
{
    public function authenticate(string $username, string $password): ?User
    {
        // Кастомная логика аутентификации
    }
    
    public function getUserById(int $id): ?User
    {
        // Загрузка пользователя по ID
    }
}
```

Зарегистрируйте провайдер в контейнере:

```php
$container->set('auth_provider', new CustomAuthProvider());
```

### Собственные модели

Вы можете расширить модели `User`, `Role`, `Permission` для добавления дополнительной логики.

```php
<?php
namespace App\Models;

use Architect\Auth\Models\User as BaseUser;

class User extends BaseUser
{
    public function getFullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
```

Укажите использование кастомной модели в конфигурации:

```json
{
    "user_model": "App\\Models\\User",
    "role_model": "App\\Models\\Role",
    "permission_model": "App\\Models\\Permission"
}
```

## Лучшие практики

1. **Всегда хешируйте пароли** – используйте алгоритмы bcrypt или argon2.

2. **Используйте middleware для защиты маршрутов** – не полагайтесь только на проверки в контроллерах.

3. **Принцип наименьших привилегий** – назначайте пользователям только те разрешения, которые им действительно нужны.

4. **Регулярно обновляйте JWT-секрет** – в production используйте длинные случайные строки.

5. **Логируйте события аутентификации** – это поможет в анализе безопасности.

6. **Ограничивайте попытки входа** – реализуйте защиту от brute-force (например, через middleware RateLimit).

7. **Используйте HTTPS** – особенно для передачи паролей и JWT-токенов.

## Частые вопросы

**Вопрос: Как добавить новое разрешение?**

Ответ: Добавьте его в конфигурацию `permissions` и назначьте нужным ролям в `roles`.

**Вопрос: Можно ли иметь пользователя с несколькими ролями?**

Ответ: Да, модель пользователя поддерживает множество ролей через отношение many-to-many. Используйте метод `$user->addRole('role_name')`.

**Вопрос: Как интегрировать аутентификацию через социальные сети (OAuth)?**

Ответ: Создайте кастомный провайдер, который будет взаимодействовать с OAuth-провайдером, и зарегистрируйте его.

**Вопрос: Как сбросить пароль пользователя?**

Ответ: Используйте встроенный функционал сброса пароля (если реализован) или создайте свой, используя модель `User` и метод `setPassword()`.

**Вопрос: Как отключить аутентификацию для определённого маршрута?**

Ответ: Не добавляйте middleware `auth` или `role` для этого маршрута.

**Вопрос: Как получить список всех пользователей с определённой ролью?**

Ответ: Используйте модель `Role`:

```php
$role = Role::findByName('admin');
$users = $role->users;