# Тестирование

Тестирование – важная часть разработки надёжных приложений. Architect RED 2 не включает встроенный фреймворк для тестирования, но полностью совместим с популярными инструментами, такими как PHPUnit, Pest и Codeception. В этой главе описаны подходы к тестированию различных компонентов системы.

## Установка PHPUnit

Добавьте PHPUnit в проект через Composer:

```bash
composer require --dev phpunit/phpunit
```

Создайте конфигурационный файл `phpunit.xml` в корне проекта:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.5/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
</phpunit>
```

## Структура тестов

Рекомендуемая структура каталогов:

```
tests/
├── Unit/
│   ├── Services/
│   ├── Models/
│   └── ...
├── Feature/
│   ├── Controllers/
│   ├── Api/
│   └── ...
└── TestCase.php
```

## Базовый класс TestCase

Создайте базовый класс `TestCase` в `tests/TestCase.php`, который будет инициализировать контейнер зависимостей и окружение.

```php
<?php

namespace Tests;

use Architect\Core\Container;
use Architect\Support\ServiceProvider;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
        $provider = new ServiceProvider($this->container);
        $provider->register();
        // При необходимости загрузите конфигурацию для тестов
    }

    protected function tearDown(): void
    {
        $this->container->reset();
        parent::tearDown();
    }
}
```

## Модульное тестирование (Unit)

### Тестирование сервисов

Сервисы, зарегистрированные в контейнере, можно тестировать, извлекая их из контейнера или создавая вручную.

```php
namespace Tests\Unit\Services;

use Tests\TestCase;
use Architect\Services\Logger\Logger;

class LoggerTest extends TestCase
{
    public function test_logger_can_log_message()
    {
        $logger = $this->container->get('logger');
        $logger->info('Test message');
        $this->assertTrue(true); // Проверка, что исключений нет
    }
}
```

### Тестирование моделей

Для тестирования моделей, использующих базу данных, рекомендуется использовать SQLite в памяти.

```php
namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Modules\User\Model\UserModel;

class UserModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Настройка тестовой БД
        $this->setupTestDatabase();
    }

    public function test_user_can_be_created()
    {
        $model = new UserModel($this->container);
        $id = $model->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        $this->assertIsInt($id);
    }
}
```

## Функциональное тестирование (Feature)

### Тестирование контроллеров

Для тестирования контроллеров можно использовать симуляцию HTTP-запросов. Architect не предоставляет встроенных HTTP-тестовых утилит, но вы можете использовать `symfony/http-foundation` для создания запросов.

```php
namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Architect\Services\Routing\HttpRequest;
use App\Modules\Home\Controller\HomeController;

class HomeControllerTest extends TestCase
{
    public function test_index_returns_view()
    {
        $request = new HttpRequest();
        $controller = new HomeController($this->container);
        $response = $controller->index($request);
        $this->assertStringContainsString('Добро пожаловать', $response);
    }
}
```

### Тестирование маршрутов

Чтобы протестировать полный цикл запроса-ответа, можно использовать `phpunit` вместе с встроенным веб-сервером PHP или инструментами вроде `guzzlehttp/guzzle`.

Пример теста API-маршрута:

```php
namespace Tests\Feature\Api;

use Tests\TestCase;
use GuzzleHttp\Client;

class ApiTest extends TestCase
{
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new Client(['base_uri' => 'http://localhost:8080']);
    }

    public function test_api_users_returns_json()
    {
        $response = $this->client->get('/api/users');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getBody()->getContents());
    }
}
```

## Тестирование с использованием баз данных

### Миграции для тестов

Используйте миграции Axiom ORM для создания и отката схемы базы данных перед каждым тестом.

```php
use Axiom\Orm\Orm;

protected function migrateUp()
{
    Orm::raw('CREATE TABLE users (...)')->execute();
}

protected function migrateDown()
{
    Orm::raw('DROP TABLE users')->execute();
}
```

### Использование транзакций

Чтобы изолировать тесты, можно оборачивать каждый тест в транзакцию и откатывать её после завершения.

```php
use Axiom\Orm\Orm;

protected function setUp(): void
{
    parent::setUp();
    Orm::beginTransaction();
}

protected function tearDown(): void
{
    Orm::rollback();
    parent::tearDown();
}
```

## Mocking и Stubs

Используйте PHPUnit Mocking для замены зависимостей.

```php
public function test_controller_uses_mocked_service()
{
    $mockService = $this->createMock(UserService::class);
    $mockService->method('getActiveUsers')
        ->willReturn([['id' => 1, 'name' => 'Test']]);

    $controller = new UserController($mockService);
    $result = $controller->index();
    $this->assertCount(1, $result);
}
```

## Тестирование событий (Statements)

Statements – это хуки жизненного цикла приложения. Вы можете тестировать, что определённые события вызываются с правильными параметрами.

```php
public function test_core_init_event_fires()
{
    $fired = false;
    $this->container->get('statement')->on('core_init', function() use (&$fired) {
        $fired = true;
    });

    // Запуск инициализации
    $this->container->get('apps')->boot();
    $this->assertTrue($fired);
}
```

## Интеграционное тестирование с Blueprint

Для тестирования шаблонов Blueprint можно использовать рендеринг строк.

```php
public function test_blueprint_template_renders_correctly()
{
    $blueprint = $this->container->get('blueprint');
    $output = $blueprint->renderString('Hello {{ name }}', ['name' => 'World']);
    $this->assertEquals('Hello World', $output);
}
```

## Тестирование middleware

Middleware можно тестировать изолированно, создавая фиктивные запросы и ответы.

```php
use Architect\Services\Mvc\Middleware\AuthMiddleware;
use Architect\Services\Routing\HttpRequest;

public function test_auth_middleware_redirects_guests()
{
    $request = new HttpRequest();
    $middleware = new AuthMiddleware($this->container);
    $response = $middleware->process($request, $handler);
    $this->assertEquals(302, $response->getStatusCode());
}
```

## Покрытие кода (Code Coverage)

Генерируйте отчёт о покрытии кода с помощью PHPUnit:

```bash
./vendor/bin/phpunit --coverage-html coverage
```

Убедитесь, что в `phpunit.xml` включена настройка coverage.

## Непрерывная интеграция (CI)

Настройте CI-пайплайн (GitHub Actions, GitLab CI, Jenkins) для автоматического запуска тестов.

Пример `.github/workflows/phpunit.yml`:

```yaml
name: PHPUnit Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install dependencies
        run: composer install --no-progress --prefer-dist
      - name: Run tests
        run: ./vendor/bin/phpunit
```

## Рекомендации

- **Изолируйте тесты**: Каждый тест должен быть независимым.
- **Используйте фикстуры**: Для сложных данных используйте фабрики (например, Faker).
- **Тестируйте граничные случаи**: Не только happy path, но и ошибки.
- **Следите за скоростью**: Тесты должны выполняться быстро, используйте SQLite в памяти.
- **Интегрируйте статический анализ**: Добавьте в CI проверки PHPStan, Psalm.

## Заключение

Тестирование приложений на Architect RED 2 не отличается от тестирования любого другого PHP-приложения. Используйте стандартные инструменты и практики, адаптировав их к архитектуре фреймворка. Это обеспечит стабильность и качество вашего кода.

Дополнительные сведения см. в разделах [Сервисы](services.md) и [Конфигурация](configuration.md).