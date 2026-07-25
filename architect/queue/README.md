# Architect Queue

Система очередей для Architect Framework, аналогичная Laravel Horizon / Symfony Messenger.

## Установка

Установите пакет через Composer:

```bash
composer require architect/queue
```

Пакет автоматически регистрирует сервис-провайдер `Architect\Queue\Providers\QueueServiceProvider`.

## Конфигурация

Создайте файл конфигурации `app/config/queue.json` (или используйте конфигурацию по умолчанию).

Пример конфигурации:

```json
{
    "default": "sync",
    "connections": {
        "sync": {
            "driver": "sync"
        },
        "database": {
            "driver": "database",
            "table": "queue_jobs",
            "connection": "default"
        },
        "redis": {
            "driver": "redis",
            "host": "127.0.0.1",
            "port": 6379,
            "password": null,
            "database": 0,
            "prefix": "queue:",
            "timeout": 0.0
        },
        "beanstalkd": {
            "driver": "beanstalkd",
            "host": "127.0.0.1",
            "port": 11300,
            "timeout": 60
        },
        "sqs": {
            "driver": "sqs",
            "key": "",
            "secret": "",
            "region": "us-east-1",
            "queue": "https://sqs.us-east-1.amazonaws.com/your-account-id/your-queue-name",
            "prefix": "queue:"
        },
        "rabbitmq": {
            "driver": "rabbitmq",
            "host": "127.0.0.1",
            "port": 5672,
            "vhost": "/",
            "user": "guest",
            "password": "guest",
            "queue": "default",
            "exchange": "amq.direct"
        }
    },
    "queues": {
        "default": "default",
        "high": 3,
        "low": 1
    },
    "retry": {
        "max_attempts": 3,
        "delay": 60,
        "exponential": true
    },
    "worker": {
        "timeout": 60,
        "sleep": 3,
        "max_jobs": 100,
        "memory_limit": 128,
        "stop_on_empty": false
    },
    "failed": {
        "driver": "database",
        "connection": "default",
        "table": "failed_jobs"
    },
    "middleware": [
        "Architect\Queue\Middleware\LoggingMiddleware"
    ]
}
```

## Создание задач

Задача — это класс, реализующий интерфейс `Architect\Queue\Contracts\JobInterface`. Проще всего унаследовать от абстрактного класса `Architect\Queue\Jobs\Job`.

Пример задачи:

```php
<?php

namespace App\Jobs;

use Architect\Queue\Jobs\Job;

class SendWelcomeEmail extends Job
{
    protected string $queue = 'default';
    protected int $maxAttempts = 3;
    protected int $delay = 0;

    public function __construct(
        protected string $email,
        protected string $name
    ) {}

    public function handle(): void
    {
        // Логика отправки email
        mail($this->email, "Welcome, {$this->name}!", "Hello!");
    }
}
```

## Диспетчеризация задач

Используйте диспетчер `queue.dispatcher`:

```php
$dispatcher = $container->get('queue.dispatcher');
$dispatcher->dispatch(new SendWelcomeEmail('user@example.com', 'John'));
```

Можно указать очередь, задержку и соединение:

```php
$dispatcher->dispatch(
    new SendWelcomeEmail('user@example.com', 'John'),
    'high', // очередь
    'database', // соединение
    60 // задержка в секундах
);
```

## Обработка задач (Worker)

Запустите воркер через консольную команду:

```bash
php arc queue:work --queue=default --connection=database
```

Доступные опции:

- `--queue` – имя очереди (по умолчанию `default`)
- `--connection` – соединение из конфигурации (по умолчанию используется `default`)
- `--sleep` – время сна при отсутствии задач (секунды)
- `--timeout` – максимальное время выполнения задачи (секунды)
- `--max-jobs` – максимальное количество задач перед остановкой
- `--memory` – лимит памяти в МБ
- `--stop-on-empty` – остановиться, если очередь пуста

## Другие команды

- `queue:status` – показать статус воркера
- `queue:flush` – очистить очередь
- `queue:retry` – повторить неудачные задачи

## События

Система генерирует события:

- `JobProcessing` – перед выполнением задачи
- `JobProcessed` – после успешного выполнения
- `JobFailed` – при неудаче
- `JobRetrying` – при повторной попытке

Для подписки на события используйте `EventDispatcherInterface`.

## Middleware

Middleware позволяют добавлять логику до и после выполнения задачи. Пример middleware для логирования:

```php
use Architect\Queue\Middleware\JobMiddlewareInterface;
use Architect\Queue\Contracts\JobInterface;

class LoggingMiddleware implements JobMiddlewareInterface
{
    public function handle(JobInterface $job, callable $next): void
    {
        Log::info("Processing job: " . get_class($job));
        $next($job);
        Log::info("Job processed: " . get_class($job));
    }
}
```

Зарегистрируйте middleware в конфигурации `queue.json`:

```json
"middleware": [
    "App\\Queue\\Middleware\\LoggingMiddleware"
]
```

## Неудачные задачи

Неудачные задачи сохраняются в таблицу `failed_jobs`. Для просмотра и повторного запуска используйте команду `queue:retry`.

## Интеграция с Debug Panel

Система очередей интегрирована с Debug Panel Architect Framework. На вкладке "Queue" отображается статистика по задачам.

## Кастомные драйверы

Вы можете создать собственный драйвер, реализовав интерфейс `QueueDriverInterface` и зарегистрировав его в конфигурации:

```json
"custom_drivers": {
    "my_driver": "App\\Queue\\Drivers\\MyDriver"
}
```

## Тестирование

Для тестирования используйте синхронный драйвер (`sync`), который выполняет задачи немедленно.

## Лицензия

MIT