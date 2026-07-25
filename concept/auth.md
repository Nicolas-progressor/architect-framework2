### Концепция системы управления авторизацией для Architect Framework

**Цель:** создать модульную, расширяемую и безопасную систему управления авторизацией (RBAC — Role‑Based Access Control) для PHP MVC‑фреймворка Architect Framework.

**Ключевые принципы:**
* **Модульность.** Система должна быть отдельным Composer‑пакетом, не затрагивающим ядро фреймворка.
* **Гибкость.** Поддержка ролей, разрешений и групп пользователей.
* **Безопасность.** Использование современных практик хеширования паролей, защита от CSRF, XSS и других атак.
* **Простота интеграции.** Лёгкая настройка через JSON‑конфигурацию, минимальные изменения в существующем коде приложения.
* **Расширяемость.** Возможность добавления кастомных правил авторизации и интеграции с внешними сервисами аутентификации (OAuth, LDAP и т. д.).

**Основные компоненты:**
1. **Модель пользователя** (`User`) — хранит данные о пользователях.
2. **Модель роли** (`Role`) — определяет роли пользователей (администратор, модератор, гость и т. д.).
3. **Модель разрешения** (`Permission`) — описывает действия, которые могут выполнять пользователи (например, `create_post`, `edit_user`).
4. **Менеджер авторизации** (`AuthManager`) — центральный класс для проверки прав доступа.
5. **Middleware** — для проверки авторизации на уровне маршрутов.
6. **Конфигурационный файл** — настройки системы в формате JSON.
7. **Хранилище данных** — поддержка различных БД (MySQL, PostgreSQL, SQLite) через PDO.

**Функциональные возможности:**
* регистрация и аутентификация пользователей;
* управление ролями и разрешениями;
* назначение ролей пользователям;
* проверка прав доступа на уровне контроллеров и действий;
* ограничение доступа к маршрутам;
* логирование попыток доступа;
* восстановление пароля;
* поддержка сессий и JWT‑токенов.

---

### Техническое задание (ТЗ)

#### 1. Установка

Система должна устанавливаться через Composer:
```bash
composer require architect/auth-system
```

#### 2. Конфигурация

Конфигурационный файл: `app/config/auth.json`.

Пример конфигурации:
```json
{
  "driver": "database",
  "table_prefix": "auth_",
  "session_lifetime": 1440,
  "password_hash_algorithm": "bcrypt",
  "password_cost": 12,
  "jwt_secret": "your-jwt-secret-key",
  "jwt_ttl": 3600,
  "default_role": "guest",
  "roles": {
    "admin": {
      "permissions": ["*"],
      "description": "Administrator with full access"
    },
    "user": {
      "permissions": ["view_posts", "create_posts"],
      "description": "Regular user"
    },
    "guest": {
      "permissions": ["view_public_content"],
      "description": "Guest user"
    }
  },
  "permissions": {
    "view_posts": "View posts",
    "create_posts": "Create new posts",
    "edit_posts": "Edit own posts",
    "delete_posts": "Delete own posts",
    "manage_users": "Manage users",
    "view_public_content": "View public content"
  }
}
```

**Параметры конфигурации:**
* `driver` — тип хранилища (database, file, redis);
* `table_prefix` — префикс таблиц в БД;
* `session_lifetime` — время жизни сессии в минутах;
* `password_hash_algorithm` — алгоритм хеширования паролей (bcrypt, argon2id);
* `password_cost` — стоимость хеширования для bcrypt;
* `jwt_secret` — секретный ключ для JWT;
* `jwt_ttl` — время жизни JWT‑токена в секундах;
* `default_role` — роль по умолчанию для новых пользователей;
* `roles` — список ролей с их разрешениями;
* `permissions` — список всех доступных разрешений.

#### 3. Структура базы данных

Таблицы с префиксом из `table_prefix`:

* `users`:
    * `id` (int, PK);
    * `username` (varchar);
    * `email` (varchar, unique);
    * `password` (varchar);
    * `role_id` (int, FK);
    * `created_at` (datetime);
    * `updated_at` (datetime).

* `roles`:
    * `id` (int, PK);
    * `name` (varchar, unique);
    * `description` (text);
    * `created_at` (datetime);
    * `updated_at` (datetime).

* `permissions`:
    * `id` (int, PK);
    * `name` (varchar, unique);
    * `description` (text);
    * `created_at` (datetime);
    * `updated_at` (datetime).

* `role_permission`:
    * `role_id` (int, FK);
    * `permission_id` (int, FK).

#### 4. API системы

**Классы и методы:**

1. `AuthManager`:
    * `login(string $username, string $password): bool` — аутентификация пользователя;
    * `logout(): void` — выход из системы;
    * `isLoggedIn(): bool` — проверка авторизации;
    * `getUser(): ?User` — получение текущего пользователя;
    * `hasPermission(string $permission): bool` — проверка разрешения;
    * `assignRole(User $user, string $roleName): bool` — назначение роли;
    * `revokeRole(User $user, string $roleName): bool` — отзыв роли.

2. `User`:
    * `getId(): int`;
    * `getUsername(): string`;
    * `getEmail(): string`;
    * `getRole(): Role`;
    * `hasRole(string $roleName): bool`.

3. `Role`:
    * `getName(): string`;
    * `getPermissions(): array`;
    * `hasPermission(string $permissionName): bool`.

#### 5. Middleware

Создать middleware `AuthMiddleware` для защиты маршрутов:

```php
// Пример использования в маршрутизаторе
$router->get('/admin', [AuthMiddleware::class, 'handle'], [AdminController::class, 'index']);
```

Middleware должен:
* проверять авторизацию пользователя;
* перенаправлять на страницу входа, если пользователь не авторизован;
* проверять разрешения для защищённых маршрутов.

#### 6. Интеграция с фреймворком

1. Автозагрузка сервиса через Composer.
2. Регистрация middleware в ядре фреймворка.
3. Создание базового контроллера `AuthController` с действиями:
    * `loginAction()` — форма входа;
    * `authenticateAction()` — обработка формы входа;
    * `logoutAction()` — выход;
    * `registerAction()` — регистрация;
    * `forgotPasswordAction()` — восстановление пароля.
4. Создание базовых шаблонов для форм входа и регистрации.

#### 7. Документация

Предоставить:
* руководство по установке и настройке;
* примеры использования API;
* описание конфигурационных параметров;
* инструкции по миграции БД;
* рекомендации по безопасности.

#### 8. Тестирование

Обеспечить покрытие тестами:
* юнит‑тесты для основных классов;
* интеграционные тесты для проверки авторизации и разрешений;
* тесты безопасности (проверка на SQL‑инъекции, CSRF и т. д.).

#### 9. Требования к окружению

* PHP $\geq 8{,}1$;
* Composer;
* поддерживаемые БД: MySQL $\geq 5{,}7$, PostgreSQL $\geq 10$, SQLite $\geq 3{,}8$;
* расширение PDO для выбранной БД;
* расширение OpenSSL для JWT.

---
