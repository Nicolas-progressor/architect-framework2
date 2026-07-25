# Отладка (Debug)

Компонент Debug предоставляет инструменты для отладки и профилирования приложения в режиме разработки. Ключевым элементом является **Debug Panel** – интерактивная панель, отображающая информацию о запросе, производительности, логах, запросах к БД и состоянии сервисов.

## Debug Panel

Debug Panel – это веб-интерфейс, который встраивается в ответ приложения (в виде плавающей панели внизу страницы). Панель доступна только когда включён режим отладки и IP-адрес пользователя находится в списке разрешённых.

### Включение/выключение

Настройки Debug Panel находятся в `app/config/debug.json`:

```json
{
    "enabled": true,
    "allowed_ips": ["127.0.0.1", "::1"],
    "skip_routes": ["/api/*", "/admin/*"],
    "collectors": ["request", "database", "logs", "services", "performance"]
}
```

- `enabled` – глобальный флаг.
- `allowed_ips` – список IP-адресов, для которых показывается панель.
- `skip_routes` – шаблоны URL, для которых панель не отображается.
- `collectors` – сборщики данных, которые активируются.

### Сборщики (Collectors)

Каждый сборщик отвечает за сбор определённого типа информации:

- **request** – данные HTTP-запроса (метод, URI, заголовки, куки, сессия).
- **database** – выполненные SQL-запросы с временем выполнения и стеком вызовов.
- **logs** – записи логгера (PSR-3) сгруппированные по каналам.
- **services** – список зарегистрированных сервисов в контейнере и их состояние.
- **performance** – метрики производительности (время выполнения, использование памяти, включённые расширения PHP).

## Класс Debug

Основной класс – `Architect\Services\Debug\Debug`. Он управляет сборщиками и интеграцией с приложением.

### Методы

- `enable()` / `disable()` – включить/выключить отладку.
- `isEnabled(): bool` – проверка состояния.
- `log(string $message, string $category = 'info', array $context = [])` – запись сообщения в лог панели.
- `getCollector(): DebugCollector` – получить объект сборщика.
- `addCollector(string $name, CollectorInterface $collector)` – добавить пользовательский сборщик.

### Использование в коде

Вы можете добавлять отладочные сообщения прямо из приложения:

```php
$debug = $container->get('debug');
$debug->log('User logged in', 'auth', ['user_id' => $userId]);
```

Сообщение появится во вкладке **Logs** Debug Panel.

## Интеграция с логгером

Debug Panel автоматически перехватывает сообщения, отправленные в логгер (сервис `logger`), и отображает их во вкладке **Logs**. Для этого используется callback, регистрируемый в `ServiceProvider`.

## Профилирование запросов к БД

При использовании Axiom ORM с включённым Debug каждый SQL-запрос записывается вместе с временем выполнения и трассировкой. Это позволяет выявлять медленные запросы и N+1 проблемы.

## Пользовательские сборщики

Вы можете создать собственный сборщик, реализовав `CollectorInterface`, и зарегистрировать его в Debug.

Пример сборщика, собирающего информацию о кэше:

```php
use Architect\Services\Debug\CollectorInterface;

class CacheCollector implements CollectorInterface
{
    private array $stats = [];

    public function collect(): array
    {
        return $this->stats;
    }

    public function getName(): string
    {
        return 'cache';
    }

    public function addHit(string $key): void
    {
        $this->stats['hits'][] = $key;
    }
}
```

Регистрация:

```php
$debug = $container->get('debug');
$debug->addCollector('cache', new CacheCollector());
```

## Отключение Debug в production

В production окружении Debug должен быть отключён. Это происходит автоматически, если `APP_ENV=production` и в конфигурации `debug.enabled` установлено в `false`. Также рекомендуется отключить отображение ошибок PHP (`display_errors = Off`).

## Доступ к Debug Panel через API

Debug Panel также предоставляет JSON API для программного доступа к собранным данным. По умолчанию API недоступен, но его можно включить, добавив маршрут:

```json
{
    "route": "/_debug/api",
    "controller": "Architect\\Services\\Debug\\DebugApiController",
    "methods": ["GET"]
}
```

Запрос `GET /_debug/api` вернёт JSON со всеми данными сборщиков.

## Расширение панели

Вы можете модифицировать внешний вид Debug Panel, переопределив шаблоны. Они находятся в `architect/Services/Debug/Resources/views/`. Однако это требует глубокого понимания внутренней структуры.

## Примеры

### Добавление кастомной информации в панель

Через событие `debug.collect` можно добавить данные в любой сборщик.

```php
$debug = $container->get('debug');
$debug->getCollector()->addData('custom', ['foo' => 'bar']);
```

### Логирование исключений

Debug Panel автоматически перехватывает исключения и отображает их во вкладке **Exceptions**. Дополнительно можно логировать их в файл через сервис `errors`.

## Заключение

Компонент Debug предоставляет мощный набор инструментов для отладки приложений на Architect RED 2. Использование Debug Panel значительно ускоряет процесс разработки и диагностики проблем.

Дополнительные сведения см. в [документации по отладке](../docs2/debugging.md).