# Контейнер зависимостей (Container)

Контейнер зависимостей (Dependency Injection Container, DIC) – центральный компонент Architect RED 2, реализующий инверсию управления (IoC) и внедрение зависимостей. Контейнер отвечает за создание и управление жизненным циклом сервисов, обеспечивая слабую связанность и тестируемость кода.

## Интерфейс ContainerInterface

Контейнер реализует `Architect\Core\Contracts\ContainerInterface`, который расширяет PSR-11 `ContainerInterface`. Дополнительные методы:

- `set(string $id, mixed $concrete)` – регистрирует готовый экземпляр.
- `factory(string $id, callable $factory)` – регистрирует фабрику для ленивого создания.
- `bind(string $id, string $concrete)` – связывает идентификатор с именем класса.
- `afterResolving(string $id, callable $callback)` – добавляет колбэк, вызываемый после разрешения сервиса.
- `reset()` – очищает кэш экземпляров.

## Реализация Container

Класс `Architect\Core\Container` предоставляет конкретную реализацию. Он хранит три типа записей:

1. **instances** – уже созданные экземпляры.
2. **factories** – callable-фабрики.
3. **bindings** – привязки идентификатора к имени класса.

### Порядок разрешения

При вызове `get($id)` контейнер проверяет:

1. Существует ли экземпляр в `instances`? Если да – возвращает его.
2. Зарегистрирована ли фабрика? Если да – вызывает её, сохраняет результат в `instances` и возвращает.
3. Есть ли привязка к классу? Если да – создаёт экземпляр через `new $class($container)` (контейнер передаётся в конструктор), сохраняет и возвращает.
4. Если ничего не найдено, выбрасывается `RuntimeException`.

## Регистрация сервисов

### Через set()

```php
$container->set('config', ['debug' => true]);
```

### Через factory()

```php
$container->factory('logger', function($c) {
    return new Logger($c->get('config'));
});
```

### Через bind()

```php
$container->bind('mailer', 'App\Services\Mailer');
```

При первом обращении к `mailer` будет создан экземпляр `App\Services\Mailer` с передачей контейнера в конструктор.

## Внедрение зависимостей

Контейнер автоматически внедряет зависимости в конструкторы сервисов, если класс зарегистрирован через `bind()` или создаётся через фабрику.

Пример класса:

```php
class UserService
{
    public function __construct(
        private Logger $logger,
        private Config $config
    ) {}
}
```

При разрешении `UserService` контейнер рекурсивно разрешит `Logger` и `Config`.

## Жизненный цикл

### Singleton vs Prototype

По умолчанию все сервисы, созданные через фабрику или привязку, сохраняются как синглтоны (один экземпляр на контейнер). Чтобы каждый раз создавать новый экземпляр, используйте фабрику без сохранения:

```php
$container->factory('transient', function($c) {
    return new TransientService();
});
// Но всё равно будет сохранён, потому что контейнер кэширует результат фабрики.
```

Для настоящего prototype можно использовать `bind` с классом, у которого нет состояния, или обернуть в фабрику, которая каждый раз возвращает новый объект.

### Очистка экземпляров

Метод `reset()` очищает все сохранённые экземпляры, но оставляет фабрики и привязки. Полезно в тестах.

## Колбэки afterResolving

Позволяют выполнить дополнительную настройку сервиса после его создания.

```php
$container->afterResolving('logger', function($logger) {
    $logger->setLevel('debug');
});
```

Если сервис уже разрешён, колбэк вызывается немедленно.

## Интеграция с ServiceProvider

Основная регистрация сервисов происходит через `Architect\Support\ServiceProvider`. Он регистрирует все системные сервисы (request, router, view и т.д.) в методе `register()`.

Вы можете создавать собственные провайдеры, расширяя `AbstractService` или реализуя произвольный класс с методом `register()`.

## Получение контейнера

Глобальный экземпляр контейнера хранится в `Architect\Core\Container::getInstance()` (синглтон). Однако предпочтительнее использовать внедрение через конструктор.

В контроллерах контейнер доступен через свойство `$this->container`.

## Тестирование с контейнером

В тестах можно создать новый контейнер, зарегистрировать тестовые doubles и проверить работу сервисов.

```php
$container = new Container();
$container->set('db', new MockDatabase());
$service = new UserService($container->get('db'));
```

## Расширение контейнера

### Декоратор

Вы можете обернуть контейнер в декоратор для добавления логирования, кэширования или других функций.

```php
class LoggingContainer implements ContainerInterface
{
    public function __construct(private ContainerInterface $inner) {}

    public function get($id)
    {
            $start = microtime(true);
            $instance = $this->inner->get($id);
            $time = microtime(true) - $start;
            log("Resolved $id in {$time}s");
            return $instance;
    }
    // ... остальные методы
}
```

### Кастомные резолверы

Architect не поддерживает кастомные резолверы из коробки, но вы можете расширить класс `Container` и переопределить метод `get()`.

## Примеры

### Регистрация кастомного сервиса

```php
// В сервис-провайдере
public function register()
{
    $this->container->factory('geo', function($c) {
        return new GeoService($c->get('config'));
    });
}
```

### Использование в контроллере

```php
class ApiController extends Controller
{
    public function __construct(private GeoService $geo)
    {}

    public function index()
    {
        $location = $this->geo->locate($ip);
        return $this->json($location);
    }
}
```

## Заключение

Контейнер зависимостей Architect RED 2 предоставляет мощный и простой механизм для управления зависимостями, соответствующий современным стандартам PHP. Использование DI улучшает модульность, тестируемость и поддерживаемость кода.

Дополнительные сведения см. в [документации по сервисам](../docs2/services.md).