# Логирование (Logger)

Компонент Logger предоставляет PSR-3 совместимый интерфейс для записи логов. Он поддерживает несколько каналов (channels), писателей (writers) и уровни логирования. Логи могут записываться в файлы, syslog, базу данных или отправляться во внешние сервисы (например, Sentry).

## Конфигурация

Настройки логирования находятся в `app/config/logger.json`:

```json
{
    "default_channel": "app",
    "channels": {
        "app": {
            "driver": "file",
            "path": "app/logs/app_{date}.log",
            "level": "debug",
            "format": "[{datetime}] {level} {message} {context}"
        },
        "error": {
            "driver": "file",
            "path": "app/logs/error_{date}.log",
            "level": "error",
            "format": "JSON"
        },
        "syslog": {
            "driver": "syslog",
            "ident": "architect",
            "level": "info"
        }
    }
}
```

### Параметры канала

- `driver` – тип писателя (`file`, `syslog`, `stream`, `null`).
- `path` – путь к файлу (для драйвера `file`). Плейсхолдер `{date}` заменяется на текущую дату.
- `level` – минимальный уровень логирования (`debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`).
- `format` – формат записи (`text`, `json`, `line`).
- Дополнительные параметры в зависимости от драйвера.

## Использование

### Получение логгера

Логгер доступен через контейнер зависимостей:

```php
$logger = $container->get('logger');
```

Или через статический фасад (если зарегистрирован):

```php
use Architect\Services\Logger\Logger;

Logger::info('Message');
```

### Запись логов

```php
$logger->info('User logged in', ['user_id' => 123]);
$logger->error('Database connection failed', ['exception' => $e]);
```

Доступные методы соответствуют уровням PSR-3: `emergency`, `alert`, `critical`, `error`, `warning`, `notice`, `info`, `debug`.

### Логирование с каналом

Вы можете указать канал при записи:

```php
$logger->channel('error')->error('Something went wrong');
```

Или использовать метод `logWithChannel`:

```php
$logger->logWithChannel('warning', 'Deprecated function called', [], 'app');
```

## Каналы (Channels)

Каналы позволяют разделять логи по назначению. Например, логи приложения, логи ошибок, логи безопасности. Каждый канал может иметь свой писатель и уровень.

### Создание канала через конфигурацию

Добавьте запись в `channels`:

```json
{
    "channels": {
        "security": {
            "driver": "file",
            "path": "app/logs/security.log",
            "level": "info"
        }
    }
}
```

### Динамическое создание канала

```php
$logger->addChannel('security', [
    'driver' => 'file',
    'path' => 'app/logs/security.log',
    'level' => 'info'
]);
```

## Писатели (Writers)

Писатель – это объект, который непосредственно записывает лог в целевое хранилище. Architect включает следующие писатели:

- `FileWriter` – запись в файл.
- `SyslogWriter` – запись в syslog.
- `StreamWriter` – запись в поток (например, `php://stderr`).
- `NullWriter` – игнорирование логов (для тестов).

### Создание пользовательского писателя

Реализуйте интерфейс `WriterInterface`:

```php
use Architect\Services\Logger\Contracts\WriterInterface;

class DatabaseWriter implements WriterInterface
{
    public function write(string $message, array $context = []): void
    {
        // сохранить $message в БД
    }
}
```

Зарегистрируйте его через конфигурацию или программно.

## Форматирование

Форматтер преобразует массив данных лога (уровень, сообщение, контекст, дата) в строку. Доступные форматеры:

- `TextFormatter` – текстовый формат с плейсхолдерами.
- `JsonFormatter` – JSON-представление.
- `LineFormatter` – однострочный вывод.

### Настройка формата

В конфигурации канала укажите `format` и при необходимости `format_template`:

```json
{
    "format": "text",
    "format_template": "[{datetime}] {level} {message} {context}"
}
```

Плейсхолдеры:
- `{datetime}` – дата и время.
- `{level}` – уровень лога.
- `{message}` – сообщение.
- `{context}` – контекст в JSON.
- `{channel}` – имя канала.

## Интеграция с Debug Panel

Логгер автоматически отправляет записи в Debug Panel, если Debug включён. Это позволяет просматривать логи прямо в браузере без необходимости открывать файлы.

## Обработка ошибок и исключений

Сервис `errors` использует логгер для записи информации об ошибках. Настройки уровня логирования ошибок можно задать в `app/config/errors.json`.

## Ротация логов

Файловые логи могут ротироваться по дате (благодаря плейсхолдеру `{date}`). Ручная ротация не предусмотрена, но можно использовать внешние инструменты (logrotate) или реализовать кастомный писатель.

## Тестирование

В тестах можно использовать `NullWriter` или мок-логгер, чтобы не создавать реальные файлы.

```php
$logger = new Logger(new NullWriter());
$logger->info('Test'); // ничего не произойдёт
```

## Примеры

### Настройка логгера для production

```json
{
    "default_channel": "app",
    "channels": {
        "app": {
            "driver": "file",
            "path": "/var/log/architect/app.log",
            "level": "warning",
            "format": "json"
        },
        "error": {
            "driver": "syslog",
            "ident": "architect",
            "level": "error"
        }
    }
}
```

### Логирование с контекстом

```php
$logger->warning('Slow query', [
    'sql' => $sql,
    'time' => 1.23,
    'trace' => debug_backtrace()
]);
```

### Создание канала для Slack (пример)

Используя стороннюю библиотеку (например, `monolog/monolog`), можно создать канал, отправляющий сообщения в Slack.

```php
use Monolog\Logger as MonologLogger;
use Monolog\Handler\SlackWebhookHandler;

$slackHandler = new SlackWebhookHandler('webhook_url', '#alerts');
$monolog = new MonologLogger('slack');
$monolog->pushHandler($slackHandler);

// Интеграция с Architect Logger через адаптер
```

## Заключение

Компонент Logger предоставляет гибкую и расширяемую систему логирования, соответствующую стандарту PSR-3. Использование каналов и писателей позволяет адаптировать логирование под нужды любого приложения.

Дополнительные сведения см. в [документации по логированию](../docs2/logging.md).