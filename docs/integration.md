# Интеграция и расширения

Architect RED 2 спроектирован как модульная система, которую можно расширять с помощью сторонних библиотек и собственных модулей. В этой главе описаны основные способы интеграции и расширения фреймворка.

## Встроенные интеграции

### Blueprint – шаблонизатор

Blueprint – современный шаблонизатор с синтаксисом, похожим на Blade/Twig, разработанный специально для Architect. Он предоставляет мощные возможности для работы с шаблонами, включая наследование, блоки, фильтры и функции.

#### Установка

```bash
composer require architect/blueprint
```

После установки Blueprint автоматически регистрируется в контейнере сервисов и становится доступен через сервис `blueprint`.

#### Использование в контроллерах

```php
public function index()
{
    return $this->blueprint('welcome', [
        'title' => 'Добро пожаловать',
        'users' => $this->model->getUsers()
    ]);
}
```

#### Использование в представлениях

В файлах шаблонов (расширение `.blu` или `.blade.php`) можно использовать синтаксис Blueprint:

```blade
{% extends "layouts/main.blu" %}

{% block content %}
    <h1>{{ title }}</h1>
    <ul>
        {% for user in users %}
            <li>{{ user.name | upper }}</li>
        {% endfor %}
    </ul>
{% endblock %}
```

#### Конфигурация

Настройки Blueprint задаются в `app/config/template.json`:

```json
{
    "engine": "blueprint",
    "paths": ["app/template"],
    "cache": true,
    "cache_path": "cache/views",
    "debug": true
}
```

Подробнее см. [документацию Blueprint](https://github.com/architect-framework/blueprint).

### Axiom ORM – работа с базами данных

Axiom ORM – универсальный конструктор SQL-запросов с поддержкой MySQL, PostgreSQL, SQLite. Интегрирован с Architect через трейт `ModelOrmTrait`.

#### Установка

```bash
composer require axiom/orm
```

#### Настройка

Создайте файл конфигурации `app/config/database.json`:

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
            "password": "secret",
            "charset": "utf8mb4"
        }
    }
}
```

#### Использование в моделях

```php
use Architect\Services\Mvc\ModelBase;
use Axiom\Orm\Integrations\Architect\ModelOrmTrait;

class UserModel extends ModelBase
{
    use ModelOrmTrait;

    public function getActiveUsers()
    {
        return $this->db()
            ->from('users')
            ->where('status', '=', 'active')
            ->orderBy('name')
            ->get();
    }
}
```

#### Прямое использование

```php
use Axiom\Orm\Orm;

$users = Orm::table('users')
    ->where('status', '=', 'active')
    ->limit(10)
    ->get();
```

Подробнее см. [документацию Axiom ORM](https://github.com/architect-framework/axiom).

## Система аутентификации и авторизации

Architect включает модуль аутентификации с поддержкой RBAC (ролевой модели доступа). Модуль предоставляет middleware, контроллеры и модели для управления пользователями, ролями и разрешениями.

### Установка

```bash
composer require architect/auth-system
```

### Настройка

Добавьте конфигурацию в `app/config/auth.json`:

```json
{
    "driver": "session",
    "model": "Architect\\AuthSystem\\Models\\User",
    "table": "users",
    "primary_key": "id",
    "password_hash": "password_hash",
    "remember_token": "remember_token"
}
```

### Использование

```php
use Architect\AuthSystem\Helpers\Auth;

// Проверка аутентификации
if (Auth::check()) {
    $user = Auth::user();
}

// Проверка роли
if (Auth::hasRole('admin')) {
    // доступ разрешён
}

// Проверка разрешения
if (Auth::can('edit_posts')) {
    // действие разрешено
}
```

### Middleware

Добавьте middleware в маршрут:

```json
{
    "route": "/admin",
    "controller": "Admin\\Dashboard",
    "middleware": ["auth", "role:admin"]
}
```

## Статические хелперы (Statics)

Statics (ранее Units) – набор статических классов-помощников, предоставляющих удобный API для часто используемых операций.

### Доступные хелперы

- **Title** – управление заголовком страницы
- **Breadcrumbs** – навигационные цепочки
- **Html** – генерация HTML-элементов
- **Assets** – управление ресурсами (CSS, JS)
- **Query** – работа с параметрами запроса

### Использование

```php
use Architect\Statics\Statics;

// Установить заголовок
Statics::Title()->set('Моя страница')->append(' | Сайт');

// Добавить хлебные крошки
Statics::Breadcrumbs()
    ->add('Главная', '/')
    ->add('Каталог', '/catalog')
    ->add('Товар');

// Сгенерировать ссылку
echo Statics::Html()->href('about', 'О нас', ['class' => 'link']);

// Подключить CSS
echo Statics::Assets()->css('style.css');
```

### Расширение Statics

Вы можете добавить собственный хелпер, создав класс в пространстве имён `Architect\Statics\YourHelper\YourHelper` и зарегистрировав его через контейнер.

## Создание собственных расширений

### Структура расширения

Расширение – это пакет Composer, который предоставляет сервисы, конфигурацию, маршруты или шаблоны для Architect. Рекомендуемая структура:

```
my-extension/
├── src/
│   ├── Extension.php
│   ├── ServiceProvider.php
│   └── ...
├── config/
│   └── extension.json
├── routes/
│   └── extension.json
├── templates/
│   └── ...
└── composer.json
```

### Класс расширения

```php
namespace MyVendor\MyExtension;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Support\AbstractService;

class Extension extends AbstractService
{
    public function register(): void
    {
        // Регистрация сервисов
        $this->container->factory('my_service', function($c) {
            return new MyService();
        });

        // Загрузка конфигурации
        $configPath = __DIR__ . '/../config/extension.json';
        if (file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath), true);
            $this->container->get('config.loader')->merge('my_extension', $config);
        }
    }

    public function boot(): void
    {
        // Инициализация после регистрации всех сервисов
        $service = $this->container->get('my_service');
        $service->init();
    }
}
```

### Регистрация расширения

Добавьте расширение в `composer.json`:

```json
{
    "extra": {
        "architect": {
            "extensions": [
                "MyVendor\\MyExtension\\Extension"
            ]
        }
    }
}
```

Architect автоматически обнаружит и загрузит расширение при запуске.

## Интеграция с внешними API

### HTTP-клиент

Architect включает официальный пакет `architect/http-client` – полнофункциональный PSR-18 совместимый HTTP-клиент с поддержкой синхронных и асинхронных запросов, цепочками middleware, конфигурируемыми драйверами (cURL, stream, curl_multi) и интеграцией с DI-контейнером фреймворка.

Подробная документация по установке, конфигурации, использованию и API доступна в отдельном файле: [HTTP-клиент](http-client.md).

### Работа с очередями

Для асинхронной обработки задач можно интегрировать систему очередей, например Laravel Horizon или Symfony Messenger.

Пример интеграции с `symfony/messenger`:

```php
use Symfony\Component\Messenger\MessageBusInterface;

$this->container->factory('message_bus', function() {
    // Конфигурация bus
    return new \Symfony\Component\Messenger\MessageBus();
});

// Отправка сообщения
$bus = $container->get('message_bus');
$bus->dispatch(new MyMessage($data));
```

## Интеграция с фронтенд-фреймворками

### Использование с Vue.js / React

Architect может служить backend API для SPA-приложений. Настройте маршруты для API и отдавайте JSON-ответы.

Пример API-контроллера:

```php
class ApiController extends Controller
{
    public function users()
    {
        $users = $this->model->getUsers();
        return $this->json($users);
    }
}
```

Маршрут:

```json
{
    "route": "/api/users",
    "controller": "Api\\Users",
    "methods": ["GET"]
}
```

### Сборка фронтенда

Вы можете использовать Vite, Webpack или другие сборщики. Ресурсы можно разместить в `htdocs/assets/` и подключать через хелпер `Assets`.

```blade
<!DOCTYPE html>
<html>
<head>
    {{ Statics::Assets()->css('app.css') }}
</head>
<body>
    <div id="app"></div>
    {{ Statics::Assets()->js('app.js') }}
</body>
</html>
```

## Мониторинг и логирование

### Интеграция с Sentry

Для отслеживания ошибок можно использовать Sentry.

```bash
composer require sentry/sentry
```

Настройте в `app/config/errors.json`:

```json
{
    "sentry": {
        "dsn": "https://your-dsn@sentry.io/your-project",
        "environment": "production"
    }
}
```

Добавьте обработчик в сервис ошибок:

```php
use Sentry\State\HubInterface;

class SentryErrorHandler
{
    public function __construct(
        protected HubInterface $sentry
    ) {}

    public function handle(\Throwable $e): void
    {
        $this->sentry->captureException($e);
    }
}
```

### Интеграция с Monolog

Architect использует PSR-3 логгер, совместимый с Monolog. Вы можете настроить дополнительные обработчики (handlers) в конфигурации логирования.

```json
{
    "channels": {
        "slack": {
            "driver": "monolog",
            "handler": "Monolog\\Handler\\SlackWebhookHandler",
            "options": {
                "webhook_url": "https://hooks.slack.com/services/..."
            }
        }
    }
}
```

## Тестирование расширений

### PHPUnit

Создайте тесты для вашего расширения, используя PHPUnit. Architect предоставляет трейты для тестирования контейнера, HTTP-запросов и базы данных.

```php
use Architect\Testing\TestCase;

class MyExtensionTest extends TestCase
{
    public function testServiceRegistration()
    {
        $service = $this->container->get('my_service');
        $this->assertInstanceOf(MyService::class, $service);
    }
}
```

### Тестирование API

Используйте `Architect\Testing\HttpTestCase` для тестирования HTTP-эндпоинтов.

```php
use Architect\Testing\HttpTestCase;

class ApiTest extends HttpTestCase
{
    public function testUsersEndpoint()
    {
        $response = $this->get('/api/users');
        $this->assertResponseOk();
        $this->assertJson($response->getContent());
    }
}
```

## Публикация расширений

### Packagist

Опубликуйте расширение на Packagist, чтобы другие разработчики могли установить его через Composer.

1. Создайте репозиторий на GitHub/GitLab.
2. Добавьте `composer.json` с зависимостью `architect/framework`.
3. Зарегистрируйте пакет на [packagist.org](https://packagist.org).

### Документация

Предоставьте документацию по установке, настройке и использованию расширения. Хорошим примером является README.md с разделами:

- Установка
- Конфигурация
- Использование
- API Reference
- Примеры
- Лицензия

## Заключение

Architect RED 2 предоставляет множество точек расширения, позволяющих интегрировать сторонние библиотеки и создавать собственные модули. Используйте контейнер зависимостей, сервис-провайдеры и события (statements) для гибкой настройки поведения приложения.

Дополнительные сведения см. в разделах [Сервисы](services.md) и [Конфигурация](configuration.md).