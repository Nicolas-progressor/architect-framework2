# Сервисы и Dependency Injection

Architect RED 2 использует контейнер зависимостей (Dependency Injection Container, DIC) для управления сервисами и их зависимостями. Контейнер реализует PSR-11 и предоставляет возможности регистрации фабрик, связывания интерфейсов с реализациями, а также ленивого создания экземпляров.

## Контейнер зависимостей

Контейнер определён в `Architect\Core\Container` и реализует `Architect\Core\Contracts\ContainerInterface`. Он используется для хранения и разрешения всех сервисов приложения.

### Основные методы

- `set(string $id, mixed $concrete)` – регистрирует готовый экземпляр.
- `factory(string $id, callable $factory)` – регистрирует фабрику, которая будет вызвана при первом обращении.
- `bind(string $id, string $concrete)` – связывает идентификатор с именем класса; при запросе будет создан новый экземпляр с передачей контейнера в конструктор.
- `get(string $id)` – возвращает экземпляр сервиса, создавая его при необходимости.
- `has(string $id)` – проверяет, зарегистрирован ли сервис.
- `afterResolving(string $id, callable $callback)` – добавляет колбэк, который будет вызван после разрешения сервиса.
- `reset()` – очищает кэш экземпляров, но сохраняет привязки и фабрики.

### Порядок разрешения

При вызове `get()` контейнер проверяет:

1. **Существующий экземпляр** – если сервис уже был создан, возвращается он.
2. **Фабрика** – если зарегистрирована фабрика, она вызывается с контейнером в качестве аргумента.
3. **Привязка класса** – если зарегистрировано имя класса, создаётся новый экземпляр через `new $class($container)`.
4. **Исключение** – если ни один из вариантов не подходит, выбрасывается `RuntimeException`.

### Пример использования

```php
use Architect\Core\Container;

$container = new Container();

// Регистрация экземпляра
$container->set('config', ['debug' => true]);

// Регистрация фабрики
$container->factory('logger', function($c) {
    return new Logger($c->get('config'));
});

// Привязка класса
$container->bind('mailer', 'App\Services\Mailer');

// Получение сервиса
$logger = $container->get('logger');
```

## Сервис-провайдеры

Сервис-провайдеры – это классы, которые регистрируют сервисы в контейнере и настраивают их. Основной провайдер – `Architect\Support\ServiceProvider`.

### Регистрация сервисов

`ServiceProvider` регистрирует все основные сервисы приложения:

- **request** – HTTP-запрос (`HttpRequest`)
- **fs** – файловая система (`NativeFileSystem`)
- **config.loader** – загрузчик конфигурации (`ConfigLoader`)
- **logger** – логгер (`Logger`)
- **router** – маршрутизатор (`Router`)
- **view** – сервис представлений (`View`)
- **model** – сервис моделей (`Model`)
- **language** – сервис интернационализации (`Language`)
- **errors** – обработчик ошибок (`Errors`)
- **debug** – отладочная панель (`Debug`)
- **form** – система форм (`Form`)
- и многие другие.

### Жизненный цикл сервисов

1. **Регистрация** – в методе `register()` провайдер определяет фабрики и привязки.
2. **Загрузка** – сервисы создаются лениво при первом обращении.
3. **Запуск** – после регистрации всех сервисов вызывается метод `boot()`, в котором каждый сервис, имеющий метод `boot()`, инициализируется.

### Создание собственного сервис-провайдера

Вы можете создать собственный провайдер для регистрации специфичных для приложения сервисов.

```php
namespace App\Providers;

use Architect\Core\Contracts\ContainerInterface;
use Architect\Support\AbstractService;

class CustomServiceProvider extends AbstractService
{
    public function register(): void
    {
        $this->container->factory('my_service', function($c) {
            return new MyService($c->get('logger'));
        });
    }

    public function boot(): void
    {
        $service = $this->container->get('my_service');
        $service->initialize();
    }
}
```

Для подключения провайдера добавьте его в конфигурацию приложения или зарегистрируйте вручную в `bootstrap.php`.

## Внедрение зависимостей в контроллерах

Контейнер автоматически внедряет зависимости в конструкторы контроллеров. Например:

```php
namespace App\Modules\Example\Controller;

use Architect\Services\Logger\Logger;
use Architect\Services\Mvc\Controller;

class ExampleController extends Controller
{
    public function __construct(
        protected Logger $logger
    ) {}

    public function index()
    {
        $this->logger->info('Index action called');
        return $this->view('index');
    }
}
```

Контейнер разрешит `Logger` и передаст его в конструктор.

## Внедрение через методы

Вы также можете использовать внедрение зависимостей в методы действий (action methods). Для этого укажите типизированные параметры, и контейнер автоматически передаст соответствующие сервисы.

```php
public function show(
    HttpRequest $request,
    Response $response,
    Database $db
) {
    // $request, $response, $db будут автоматически внедрены
}
```

## Список доступных сервисов

Ниже приведён неполный список сервисов, зарегистрированных по умолчанию.

| Идентификатор | Класс | Описание |
|---------------|-------|----------|
| `request` | `Architect\Services\Routing\HttpRequest` | Объект HTTP-запроса |
| `router` | `Architect\Services\Routing\Router` | Маршрутизатор |
| `view` | `Architect\Services\Mvc\View` | Сервис представлений |
| `model` | `Architect\Services\Mvc\Model` | Базовый класс моделей |
| `logger` | `Architect\Services\Logger\Logger` | Логгер (PSR-3) |
| `config.loader` | `Architect\Services\Config\ConfigLoader` | Загрузчик конфигурации |
| `config` | `Architect\Services\Config\Config` | Конфигурация приложений |
| `config.router` | `Architect\Services\Config\Config` | Конфигурация маршрутизации |
| `config.logger` | `Architect\Services\Config\Config` | Конфигурация логирования |
| `config.template` | `Architect\Services\Config\Config` | Конфигурация шаблонов |
| `config.debug` | `Architect\Services\Config\Config` | Конфигурация отладки |
| `language` | `Architect\Services\I18n\Language` | Сервис интернационализации |
| `errors` | `Architect\Services\Errors\Errors` | Обработчик ошибок |
| `debug` | `Architect\Services\Debug\Debug` | Отладочная панель |
| `form` | `Architect\Services\Form\Form` | Система форм |
| `middleware.resolver` | `Architect\Services\Mvc\Middleware\MiddlewareResolver` | Резолвер middleware |
| `middleware.dispatcher` | `Architect\Services\Mvc\Middleware\MiddlewareDispatcher` | Диспетчер middleware |
| `response` | `Psr\Http\Message\ResponseInterface` | PSR-7 ответ |
| `http.response_factory` | `Architect\Services\Mvc\Http\ResponseFactory` | Фабрика PSR-7 ответов |
| `http.response_emitter` | `Architect\Services\Mvc\Http\ResponseEmitter` | Отправитель ответов |
| `apps` | `Architect\Services\App\Apps` | Менеджер приложений |
| `module.resolver` | `Architect\Services\Mvc\Resolver\ModulePathResolver` | Резолвер путей модулей |
| `mvc.context` | `Architect\Services\Mvc\Context\MvcContext` | Контекст MVC |
| `mvc.controller_loader` | `Architect\Services\Mvc\Loader\ControllerLoader` | Загрузчик контроллеров |
| `mvc.bootstrap_loader` | `Architect\Services\Mvc\Loader\ModuleBootstrapLoader` | Загрузчик бутстрапов модулей |
| `mvc.error_handler_404` | `Architect\Services\Mvc\Handler\ErrorHandler404` | Обработчик 404 ошибок |
| `mvc.renderer` | `Architect\Services\Mvc\Renderer` | Рендерер ответов |
| `pattern` | `Architect\Services\Mvc\Pattern` | Паттерн выполнения MVC |
| `template` | `Architect\Services\Template\Template` | Сервис шаблонов |
| `blueprint` | `Blueprint\Engine\Blueprint` | Шаблонизатор Blueprint (если установлен) |
| `console` | `Architect\Console\Console` | Консольный интерфейс |
| `console.factory` | `Architect\Console\Factory` | Фабрика консольных команд |
| `console.registry` | `Architect\Console\Registry` | Реестр команд |

## Расширение контейнера

### Добавление собственных сервисов

Вы можете зарегистрировать собственные сервисы в контейнере через конфигурационный файл `app/config/services.json` или вручную в файле инициализации приложения.

Пример `services.json`:

```json
{
    "services": {
        "my_service": "App\\Services\\MyService",
        "mailer": {
            "factory": "App\\Services\\MailerFactory::create"
        }
    }
}
```

### Декораторы сервисов

Для модификации существующих сервисов можно использовать паттерн «декоратор». Зарегистрируйте фабрику, которая оборачивает оригинальный сервис.

```php
$container->factory('logger', function($c) {
    $original = new Logger($c->get('config'));
    return new LoggerDecorator($original);
});
```

### Тегирование сервисов

Architect не поддерживает тегирование из коробки, но вы можете реализовать его самостоятельно, регистрируя сервисы в массиве по определённому тегу.

## Лучшие практики

- **Используйте внедрение зависимостей** вместо прямого создания объектов с помощью `new`.
- **Регистрируйте сервисы через фабрики**, если их создание требует дополнительной логики.
- **Избегайте циклических зависимостей** – если два сервиса зависят друг от друга, пересмотрите архитектуру или используйте ленивую загрузку.
- **Используйте интерфейсы** для слабой связанности. Привязывайте интерфейс к конкретной реализации через `bind()`.
- **Не злоупотребляйте сервис-локатором** – предпочитайте внедрение через конструктор явному вызову `$container->get()`.

## Отладка сервисов

Для отладки зависимостей используйте отладочную панель (Debug Panel). На вкладке **Services** отображается список всех зарегистрированных сервисов, их состояние и зависимости.

Также можно использовать консольную команду для вывода информации о сервисах:

```bash
php bin/arc service:list
```

## Заключение

Контейнер зависимостей Architect RED 2 предоставляет мощный и гибкий механизм управления зависимостями, соответствующий современным стандартам PHP. Использование DI улучшает тестируемость, поддерживаемость и гибкость приложения.

Дополнительные сведения см. в разделах [Конфигурация](configuration.md) и [Консольные команды](console.md).