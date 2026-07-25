# Architect RED 2

> Активная разработка

## Технологии
- PHP 8.1+ (строгая типизация)
- PSR-4 автозагрузка
- Statement-based MVC архитектура
- Контейнер зависимостей (DIC) — Singleton
- Bootstrap 5.3.2 (опционально, для стандартных шаблонов)

## Структура проекта

```
architect_framework_2/
├── architect/              # Ядро фреймворка
│   ├── Core/               # Основные классы
│   │   ├── Container.php   # Контейнер зависимостей (Singleton)
│   │   ├── Framework.php   # Основной класс приложения
│   │   ├── Statement.php   # Система statement-ов (жизненный цикл)
│   │   ├── EnvironmentManager.php # Управление окружением
│   │   ├── Config/         # Загрузчики конфигурации
│   │   ├── Environment/    # Определение окружения
│   │   ├── Debug/          # Отладочная панель
│   │   ├── Http/           # HTTP-компоненты
│   │   └── Contracts/      # Интерфейсы
│   ├── Services/           # Сервисы
│   │   ├── App/            # Управление приложениями
│   │   ├── Blueprint/      # Шаблонизатор Blueprint
│   │   ├── Cache/          # Кэширование
│   │   ├── Config/         # Конфигурация
│   │   ├── Console/        # Консоль
│   │   ├── Database/       # Работа с БД
│   │   ├── Debug/          # Отладочная панель
│   │   ├── Errors/         # Обработка ошибок
│   │   ├── Form/           # Формы и валидация
│   │   ├── I18n/           # Интернационализация
│   │   ├── Logger/         # Логирование
│   │   ├── Mvc/            # MVC компоненты
│   │   ├── Routing/        # Маршрутизация
│   │   └── Template/       # Шаблонизатор
│   ├── Support/            # Вспомогательные классы
│   │   ├── ServiceProviders/ # Провайдеры сервисов
│   │   ├── Traits/         # Трейты
│   │   └── Debug.php       # Фасад отладки
│   ├── auth-system/        # Система аутентификации
│   ├── http-client/        # HTTP-клиент
│   ├── blueprint-auth/     # Blueprint расширение авторизации
│   ├── blueprint-forms/    # Blueprint расширение форм
│   └── bootstrap.php       # Загрузчик
├── app/
│   ├── apps/               # Приложения
│   │   ├── home/           # Приложение home
│   │   ├── admin/          # Приложение admin
│   │   └── ...
│   ├── config/             # Общая конфигурация
│   ├── modules/            # Общие модули
│   ├── routes/             # Общие маршруты
│   └── template/           # Общие шаблоны
├── htdocs/                 # Точка входа
│   └── index.php
├── docs/                   # Документация
├── axiom/                  # ORM Axiom
├── blueprint/              # Шаблонизатор Blueprint
└── vendor/                 # Composer зависимости
```

---

## Точка входа

**Файл:** `htdocs/index.php`

```php
<?php

declare(strict_types=1);

define('ROOT_DIR', dirname(__DIR__) . '/');
define('APP_DIR', ROOT_DIR . 'app/');
define('ARC_DIR', ROOT_DIR . 'architect/');
define('ROOT_URL', '/');

require_once ARC_DIR . 'bootstrap.php';
```

---

## Bootstrap

**Файл:** `architect/bootstrap.php`

Инициализация:
1. Загрузка Composer autoloader
2. Создание EnvironmentManager (определение окружения)
3. Определение констант APP_ENV и APP_DEBUG
4. Интеграция Axiom ORM (если доступен)
5. Создание Container
6. Регистрация сервисов через AggregateServiceProvider
7. Запуск Framework

**Service Providers:**
- CoreServiceProvider
- AppsServiceProvider
- RoutingServiceProvider
- MvcServiceProvider
- HttpServiceProvider
- LoggingServiceProvider
- ErrorServiceProvider
- TemplateServiceProvider
- LanguageServiceProvider
- DatabaseServiceProvider
- HelpersServiceProvider
- CacheServiceProvider
- BlueprintServiceProvider (опционально)
- ConsoleServiceProvider (опционально)

---

## Контейнер зависимостей

**Файл:** `architect/Core/Container.php`

Реализует PSR-11. Методы:
- `set(string $id, mixed $concrete)` — регистрация экземпляра
- `factory(string $id, callable $factory)` — фабрика для ленивого создания
- `bind(string $id, string|callable $concrete)` — привязка класса
- `get(string $id)` — получение сервиса
- `has(string $id)` — проверка регистрации
- `afterResolving(string $id, callable $callback)` — колбэк после создания

---

## Environment Manager

**Файл:** `architect/Core/EnvironmentManager.php`

Определение окружения (приоритет):
1. Переменная окружения ОС `APP_ENV`
2. Файл `.env` в корне проекта
3. Константа PHP `APP_ENV`
4. Значение по умолчанию — `production`

**Окружения:** development, testing, staging, production

```php
$env = $container->get('environment');

$env->getEnvironment(); // 'development'
$env->isDevelopment();  // true/false
$env->get('database.host', 'localhost');
$env->all();
```

---

## Statements

Жизненный цикл приложения:

```
core_preinit    // Предварительная инициализация
core_init       // Инициализация ядра
core_load       // Загрузка приложения
core_post_load  // После загрузки
app_load        // Загрузка данных модуля
app_data        // Обработка данных (модель)
app_output      // Вывод (контроллер)
render          // Рендеринг представления
```

Каждый этап может иметь приоритет выполнения.

---

## Сервисы

**Зарегистрированные сервисы:**

| ID | Назначение |
|----|------------|
| `environment` | Управление окружением |
| `config` | Конфигурация приложений |
| `logger` | Логирование (PSR-3) |
| `router` | Маршрутизация URL |
| `apps` | Управление приложениями |
| `template` | Шаблонизатор |
| `view` | Представление |
| `model` | Модель |
| `language` | Язык и переводы |
| `pattern` | Обработчик MVC |
| `errors` | Обработка ошибок |
| `debug` | Отладочная панель |
| `form` | Формы и валидация |
| `database` / `db` | Работа с БД |
| `cache` | Кэширование |
| `blueprint` | Шаблонизатор Blueprint |
| `console` | Консоль |

---

## Конфигурация

**Папка:** `app/config/`

- `config.json` — общие настройки
- `apps.json` — приложения
- `router.json` — маршрутизация
- `debug.json` — отладка
- `cache.json` — кэширование
- `database.json` — база данных
- `auth.json` — аутентификация
- `blueprint.json` — шаблонизатор
- `lang.json` — язык
- `logger.json` — логирование
- `environment/` — настройки окружения

---

## Маршрутизация

**Файл:** `app/config/router.json`

```json
{
    "default_module": "home",
    "default_controller": "home",
    "default_action": "index",
    "404_module": "_404",
    "404_controller": "_404",
    "404_action": "index",
    "case_sensitive": false,
    "auto_resolve": true
}
```

### Файл маршрута

```json
{
    "default": "index",
    "routes": {
        "about": {
            "module": "about",
            "controller": "about",
            "action": "index"
        },
        "admin": {
            "module": "admin",
            "controller": "admin",
            "action": "index",
            "app": "admin"
        }
    }
}
```

### Поля маршрута

| Поле | Описание |
|------|----------|
| `module` | Имя модуля |
| `controller` | Имя контроллера |
| `action` | Имя экшена |
| `app` | Приложение |
| `template` | Шаблон |
| `notemplate` | Без шаблона |
| `var_remap` | Маппинг параметров |

### Приоритет загрузки маршрутов

1. Глобальные (`app/routes/`)
2. Конфиг приложения (`app/apps/{app}/config/routes.json`)
3. Маршруты приложения (`app/apps/{app}/routes/`)
4. Маршруты модуля

### Автоматическое разрешение

При `auto_resolve: true` и ненайденном маршруте:
- `/` → home/home/index
- `/users` → users/users/index
- `/users/profile` → users/users/profile
- `/users/profile/edit` → users/profile/edit

---

## Приложения

**Папка:** `app/apps/`

Структура приложения:
```
app/apps/{app}/
├── appbootstrap.php    # Bootstrap приложения
├── config/             # Конфигурация
│   ├── routes.json     # Маршруты
│   └── router.json     # Настройки роутера
├── modules/            # Модули приложения
├── routes/             # Дополнительные маршруты
└── template/           # Шаблоны приложения
```

### Переключение приложения

```php
$apps = $this->get('apps');
$apps->switchApp('admin');
```

---

## Модули

### Расположение

- **Прикладные:** `app/apps/{app}/modules/{module}/`
- **Общие:** `app/modules/{module}/`

### Структура модуля

```
{module}/
├── controller/
│   └── {controller}.php
├── model/
│   └── {model}.php
├── view/
│   ├── index.php
│   └── index.blu
├── elements/           # MVC Elements
├── widget/             # Widgets
├── lang/               # Языковые файлы
└── routes/             # Маршруты модуля
```

### Namespace контроллера

```php
namespace app\home\modules\users\controller;

use Architect\Services\Mvc\Controller;

class users extends Controller
{
    public function index_app_data(): void {}
    public function index_app_output(): void {}
}
```

---

## Контроллеры

**Базовый класс:** `Architect\Services\Mvc\Controller`

### Жизненный цикл (statement-based)

```php
class users extends Controller
{
    // Этап данных (модель)
    public function index_app_data(): void
    {
        $this->ext['users'] = $this->getModel('User')->getAll();
    }

    // Этап вывода (представление)
    public function index_app_output(): void
    {
        $this->display('index');
    }
}
```

### Основные методы

**Представления:**
- `display(string $template, array $data = [])` — вывести шаблон
- `render(string $template, array $data = [])` — вернуть строку
- `setTemplate(string $name)` — установить layout
- `noTemplate()` — без layout

**Ответы:**
- `json(mixed $data, int $status = 200)` — JSON
- `redirectTo(string $url, int $status = 302)` — редирект
- `html(string $content)` — HTML

**Параметры:**
- `param(string $name, string $default = '')` — параметр URL
- `segment(int $index, string $default = '')` — сегмент URL
- `get(string $id)` — сервис из контейнера

**Модели:**
- `getModel(string $name)` — загрузить модель

**Middleware:**
- `addMiddleware(string $middleware, array $options = [])`
- `middlewareOnly(string $middleware, array $actions)`
- `middlewareExcept(string $middleware, array $actions)`

---

## Модели

**Базовый класс:** `Architect\Services\Mvc\Model`

```php
use Architect\Services\Mvc\Model;

class User extends Model
{
    protected string $table = 'users';
    
    public function getActive(): array
    {
        return $this->where('active', 1)->fetchAll();
    }
}
```

### ModelBase

Для автоматического доступа к контейнеру:

```php
use Architect\Services\Mvc\ModelBase;

class User extends ModelBase
{
    public function getActive(): array
    {
        // $this->get('language') работает сразу
        return $this->where('active', 1)->fetchAll();
    }
}
```

---

## Представления

### PHP-шаблоны

**Расположение:** `view/{template}.php`

```php
<div class="container">
    <h1><?= htmlspecialchars($title) ?></h1>
    <?php foreach ($users as $user): ?>
        <p><?= htmlspecialchars($user['name']) ?></p>
    <?php endforeach; ?>
</div>
```

### Blueprint шаблонизатор

**Расположение:** `view/{template}.blu`

**Особенности:**
- Компиляция в PHP
- Наследование шаблонов (extends, block)
- Layout-система (layout, section, yield)
- 40+ встроенных фильтров и функций
- Автоматическое экранирование HTML
- MVC Elements и Widgets

**Пример:**

```blade
{% layout 'layouts/main' %}

{% section title %}Список пользователей{% endsection %}

{% section content %}
    <h1>Пользователи</h1>
    {% for user in users %}
        <p>{{ user.name }}</p>
    {% endfor %}
{% endsection %}
```

**Layout:**

```blade
<!DOCTYPE html>
<html>
<head>
    <title>{% yield title %}</title>
</head>
<body>
    {% yield content %}
</body>
</html>
```

---

## Шаблонизатор Blueprint

**Сервис:** `blueprint`

### Директивы

```blade
{% extends 'base' %}
{% layout 'layouts/main' %}
{% block content %}{% endblock %}
{% section title %}{% endsection %}
{% yield content %}
{% include 'partials/header' %}
{% element 'Alert' type='warning' %}
```

### Фильтры

```blade
{{ user.name|upper }}
{{ post.date|date('d.m.Y') }}
{{ text|truncate(100) }}
{{ html|raw }}
```

### Функции

```blade
{% for user in users %}{% endfor %}
{% if authenticated %}{% endif %}
{% set variable = 'value' %}
```

---

## Работа с базой данных

**Сервис:** `database` / `db`

### Конфигурация

**Файл:** `app/config/database.json`

```json
{
    "default": "mysql",
    "connections": {
        "mysql": {
            "driver": "mysql",
            "host": "localhost",
            "port": 3306,
            "database": "myapp",
            "username": "root",
            "password": "",
            "charset": "utf8mb4"
        },
        "sqlite": {
            "driver": "sqlite",
            "database": "database/database.sqlite"
        }
    }
}
```

### Использование

```php
use Architect\Statics\DB;

// SELECT
$users = DB::query('SELECT * FROM users')->fetchAll();
$user = DB::fetch('SELECT * FROM users WHERE id = ?', [1]);

// INSERT/UPDATE/DELETE
DB::execute('INSERT INTO users (name) VALUES (?)', ['John']);
DB::execute('UPDATE users SET active = 1 WHERE id = ?', [1]);

// Транзакции
DB::beginTransaction();
try {
    DB::execute('INSERT INTO orders ...');
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
}
```

---

## Кэширование

**Сервис:** `cache`

### Конфигурация

**Файл:** `app/config/cache.json`

```json
{
    "default": "file",
    "prefix": "arch_cache_",
    "ttl": 3600,
    "stores": {
        "file": {
            "driver": "file",
            "path": "storage/cache"
        },
        "redis": {
            "driver": "redis",
            "host": "localhost",
            "port": 6379
        },
        "array": {
            "driver": "array"
        }
    }
}
```

### Использование

```php
$cache = $this->get('cache');

// Получить
$value = $cache->get('key', 'default');

// Установить
$cache->set('key', 'value', 3600);

// Проверить
if ($cache->has('key')) { }

// Удалить
$cache->forget('key');

// Очистить
$cache->flush();
```

---

## Система аутентификации

**Пакет:** `architect/auth-system`

### Конфигурация

**Файл:** `app/config/auth.json`

```json
{
    "driver": "database",
    "table_prefix": "auth_",
    "session_lifetime": 1440,
    "password_hash_algorithm": "bcrypt",
    "jwt_secret": "secret",
    "jwt_ttl": 3600,
    "default_role": "guest",
    "roles": {
        "admin": {
            "permissions": ["*"]
        },
        "user": {
            "permissions": ["view_content", "create_posts"]
        }
    }
}
```

### Использование

```php
$auth = $this->get('auth');

// Аутентификация
$auth->login($user);
$auth->logout();
$auth->check();
$auth->user();

// Права доступа
$auth->hasPermission('create_posts');
$auth->hasRole('admin');
```

### Middleware

```php
// В контроллере
$this->middlewareExcept(AuthMiddleware::class, ['login']);
$this->middlewareOnly(RoleMiddleware::class, ['admin']);
```

---

## Формы

**Сервис:** `form`

### Использование

```php
$form = $this->get('form');

$form->setRules([
    'email' => 'required|email',
    'password' => 'required|min:6'
]);

if ($form->validate($_POST)) {
    $data = $form->getData();
} else {
    $errors = $form->getErrors();
}
```

---

## Отладочная панель

**Сервис:** `debug`

### Вкладки

| Вкладка | Описание |
|---------|----------|
| ⏱️ Time | Время выполнения по этапам |
| 💾 Memory | Использование памяти |
| 🗄️ Database | SQL-запросы |
| ⚠️ Logs | Ошибки и предупреждения |
| 🔁 Cache | Статистика кэширования |
| 🖐️ Session | Данные сессии |
| ⚙️ Environment | Конфигурация |
| 🛠️ Debug | Пользовательские данные |

### DebugDataCollector

```php
use Architect\Support\Debug;

Debug::log('message', 'info', ['data' => $value]);
Debug::startTimer('query');
Debug::stopTimer('query');
Debug::addData('results', $data);
Debug::dump('variable', $variable);
```

---

## Console

**Команда:** `php arc` или `php bin/arc`

### Основные команды

```bash
# Список команд
php arc list

# Создание
php arc make:controller UserController --module=users
php arc make:model User --module=users
php arc make:module blog
php arc make:view users/index
php arc make:migration create_users_table

# Миграции
php arc db:migrate
php arc db:rollback
php arc db:seed

# Кэширование
php arc cache:clear
php arc config:cache

# Тесты
php arc test:run
```

---

## Unit-ы (хелперы)

**Установка:** `composer require architect/units`

### Использование

```php
// Html
Unit::Html()->href('about');
Unit::Html()->tag('div', 'Content', ['class' => 'container']);
Unit::Html()->img('/uploads/photo.jpg');
Unit::Html()->icon('house');

// Breadcrumbs
Unit::Breadcrumbs()->add('Главная', '/');
$crumbs = Unit::Breadcrumbs()->all();

// Assets
Unit::Assets()->css('main.css');
Unit::Assets()->js('app.js');

// Query
$name = Unit::Query()->get('name');
$email = Unit::Query()->post('email');

// Title
Unit::Title()->set('Моя страница');
echo Unit::Title()->render();
```

---

## Документация

**Папка:** `docs/`

| Файл | Описание |
|------|----------|
| `installation.md` | Установка |
| `project-structure.md` | Структура проекта |
| `routing.md` | Маршрутизация |
| `controllers.md` | Контроллеры |
| `models.md` | Модели |
| `views.md` | Представления |
| `database.md` | База данных |
| `caching.md` | Кэширование |
| `auth.md` | Аутентификация |
| `forms.md` | Формы |
| `helpers.md` | Хелперы |
| `console.md` | Консоль |
| `debugging.md` | Отладка |
| `services.md` | Сервисы |

---

## Важные замечания

- PHP 8.1+ со строгой типизацией
- Контейнер — Singleton
- Приложения: `app/apps/{app}/`
- Общие модули: `app/modules/`
- Общие шаблоны: `app/template/`
- Общие маршруты: `app/routes/`
- Statement-based жизненный цикл
- Blueprint — мощный шаблонизатор
- Axiom ORM — опционально
- Auth System — полноценная RBAC
- Кэширование: File, Redis, Array
- Database: MySQL, PostgreSQL, SQLite
