### Концепция консольной системы для PHP MVC‑фреймворка Architect Framework

#### 1. Цели и задачи

Консольная система (CLI) должна:
* автоматизировать рутинные задачи разработки;
* обеспечивать доступ к внутренним механизмам фреймворка из командной строки;
* поддерживать расширяемость через пользовательские команды;
* предоставлять унифицированный интерфейс для всех команд;
* гарантировать совместимость с существующими компонентами Architect Framework.

#### 2. Архитектура системы

**Основные компоненты:**

1. **Console Kernel** — ядро системы, отвечающее за:
    * инициализацию окружения;
    * парсинг аргументов командной строки;
    * маршрутизацию команд;
    * обработку ошибок.

2. **Command Interface** — базовый интерфейс для всех консольных команд:
    ```php
    interface CommandInterface
    {
        public function getName(): string;
        public function getDescription(): string;
        public function execute(array $arguments, array $options): int;
        public function getArguments(): array;
        public function getOptions(): array;
    }
    ```

3. **Base Command** — абстрактный класс, реализующий базовый функционал:
    * помощь по команде (`--help`);
    * валидация аргументов;
    * вывод в консоль с форматированием.

4. **Command Registry** — реестр зарегистрированных команд, обеспечивающий:
    * динамическую загрузку команд;
    * кэширование списка команд;
    * поиск команд по имени.

5. **Input/Output Layer** — слой ввода‑вывода:
    * парсер аргументов командной строки;
    * форматированный вывод (цвета, таблицы, прогресс‑бары);
    * поддержка интерактивного ввода.

#### 3. Реализация команд

**Структура команды:**
```php
class MakeControllerCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'make:controller';
    protected string $description = 'Create a new controller class';

    public function getArguments(): array
    {
        return [
            ['name', 'Controller name (e.g., UserController)']
        ];
    }

    public function getOptions(): array
    {
        return [
            ['--resource', 'Create resource controller']
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $controllerName = $arguments['name'];
        $isResource = $options['resource'] ?? false;

        // Логика создания контроллера
        $this->info("Controller {$controllerName} created successfully!");

        return 0; // Код успешного завершения
    }
}
```

#### 4. Встроенные команды

Базовый набор команд для Architect Framework:

* **Управление проектом:**
    * `arc:info` — информация о проекте.

* **Генерация кода:**
    * `make:app` - создание приложения;
    * `make:module` - создание модуля;
    * `make:controller` — создание контроллера;
    * `make:model` — создание модели;
    * `make:view` — создание представления;
    * `make:migration` — создание миграции БД;
    * `make:route` — создание маршрута.

* **Работа с БД:**
    * `db:migrate` — выполнение миграций;
    * `db:rollback` — откат миграций;
    * `db:seed` — заполнение БД тестовыми данными.

* **Отладка и тестирование:**
    * `test:run` — запуск тестов;
    * `cache:clear` — очистка кэша;
    * `route:list` — список всех маршрутов.

* **Производительность:**
    * `optimize:autoload` — оптимизация автозагрузки;
    * `config:cache` — кэширование конфигурации.

#### 5. Механизм регистрации команд

**Способы регистрации:**

1. **Автоматическая загрузка** — сканирование директории `app/Console/Commands` для поиска классов, реализующих `CommandInterface`.

2. **Явная регистрация** — через конфигурационный файл `config/console.php`:
    ```php
    return [
        'commands' => [
            \App\Console\Commands\MakeControllerCommand::class,
            \App\Console\Commands\DbMigrateCommand::class
        ]
    ];
    ```

3. **Динамическая регистрация** — в сервисе‑провайдере:
    ```php
    class ConsoleServiceProvider extends ServiceProvider
    {
        public function register(): void
        {
            $this->app->make(ConsoleKernel::class)
                ->registerCommand(new MakeControllerCommand());
        }
    }
    ```

#### 6. Интерфейс пользователя

**Особенности взаимодействия:**

* **Автодополнение** — поддержка Tab‑автодополнения для команд и опций.
* **Справка** — встроенная помощь для каждой команды (`команда --help`).
* **Цвета и форматирование** — использование ANSI‑цветов для улучшения читаемости:
    * зелёный — успешные операции;
    * красный — ошибки;
    * жёлтый — предупреждения.
* **Прогресс‑бары** — для длительных операций (миграции, тесты).
* **Интерактивный режим** — запросы подтверждения (`y/n`).

#### 7. Обработка ошибок

**Стратегия обработки:**

* **Валидация аргументов** — проверка обязательных параметров перед выполнением.
* **Исключения** — перехват исключений с выводом понятных сообщений.
* **Коды возврата** — стандартные коды:
    * `$0` — успех;
    * `$1` — общая ошибка;
    * `$2` — синтаксическая ошибка в аргументах.

#### 8. Интеграция с MVC‑архитектурой

**Взаимодействие с компонентами:**

* **Приолжения** — Генерация схемы приложений.
* **Контроллеры** — доступ к сервисам через DI‑контейнер.
* **Модели** — использование ORM для работы с БД.
* **Маршруты** — генерация URL для тестирования.
* **Конфигурация** — чтение настроек из `config/`‑директории.
* **Кэш** — управление кэшем приложения.

#### 9. Расширяемость

**Механизмы расширения:**

* **Пользовательские команды** — разработчики могут создавать собственные команды, наследуя `BaseCommand`.
* **Хуки** — события до/после выполнения команд (`command:before`, `command:after`).
* **Плагины** — поддержка сторонних пакетов с дополнительными командами.

#### 10. Пример использования

**Создание миграции:**
```bash
php architect make:migration create_users_table --create=users
```
**Выполнение миграций:**
```bash
php architect db:migrate
```
**Очистка кэша:**
```bash
php architect cache:clear
```

---

**Преимущества предложенной концепции:**
* **Единообразие** — стандартизированный интерфейс для всех команд.
* **Гибкость** — возможность расширения под нужды проекта.
* **Интеграция** — тесная связь с компонентами MVC.
* **Удобство** — продуманный UX для разработчиков.
* **Масштабируемость** — поддержка сложных сценариев через хуки и плагины.

### возможность написания пользовательских консольных приложений в `app/console/`

#### 1. Структура директории `app/Console/`

Для удобства организации кода предлагается следующая структура:

```
app/Console/
├── Commands/          # Пользовательские консольные команды
│   ├── MakeModuleCommand.php
│   └── SeedUsersCommand.php
├── Applications/      # Отдельные консольные приложения
│   ├── DataProcessorApp.php
│   └── ReportGeneratorApp.php
├── Scheduler.php      # Планировщик задач (аналог Laravel Scheduler)
```

#### 2. Механизм обнаружения и регистрации команд

**Автоматическая регистрация** из директории `app/Console/Commands`:
* при запуске консоли система сканирует директорию;
* находит все классы, наследующие `BaseCommand` или реализующие `CommandInterface`;
* регистрирует их в `Command Registry`.

**Пример пользовательской команды** (`app/Console/Commands/SeedUsersCommand.php`):
```php
<?php

namespace App\Console\Commands;

use Architect\Console\BaseCommand;
use Architect\Console\CommandInterface;

class SeedUsersCommand extends BaseCommand implements CommandInterface
{
    protected string $name = 'db:seed-users';
    protected string $description = 'Seed database with test users';

    public function getArguments(): array
    {
        return [
            ['count', 'Number of users to create']
        ];
    }

    public function getOptions(): array
    {
        return [
            ['--admin', 'Create admin users']
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $count = (int)$arguments['count'];
        $isAdmin = $options['admin'] ?? false;

        // Логика заполнения БД
        for ($i = 0; $i < $count; $i++) {
            // Создание пользователя
            $this->info("User {$i} created");
        }

        $this->success("Successfully created {$count} users!");
        return 0;
    }
}
```

#### 3. Создание отдельных консольных приложений

**Консольное приложение** — это класс в `app/Console/Applications/`, который может содержать:
* набор связанных команд;
* собственную логику инициализации;
* зависимости от сервисов фреймворка.

**Пример приложения** (`app/Console/Applications/DataProcessorApp.php`):
```php
<?php

namespace App\Console\Applications;

use Architect\Console\ConsoleKernel;
use Architect\Console\BaseCommand;

class DataProcessorApp
{
    public function registerCommands(ConsoleKernel $kernel): void
    {
        // Регистрация набора команд для обработки данных
        $kernel->registerCommand(new class extends BaseCommand {
            protected string $name = 'data:process';
            protected string $description = 'Process raw data files';

            public function execute(array $arguments, array $options): int
            {
                $this->info('Starting data processing...');
                // Сложная логика обработки данных
                return 0;
            }
        });

        $kernel->registerCommand(new class extends BaseCommand {
            protected string $name = 'data:export';
            protected string $description = 'Export processed data';

            public function execute(array $arguments, array $options): int
            {
                $this->info('Exporting data...');
                return 0;
            }
        });
    }
}
```

#### 4. Регистрация приложений

**Способы регистрации приложений:**

1. **Автоматическая загрузка** — сканирование `app/Console/Applications/` и вызов метода `registerCommands()` у найденных классов.

2. **Явная регистрация** в `app/Console/Kernel.php`:
```php
<?php

namespace App\Console;

use Architect\Console\ConsoleKernel as BaseKernel;
use App\Console\Applications\DataProcessorApp;

class Kernel extends BaseKernel
{
    protected function loadApplications(): void
    {
        // Явная регистрация приложений
        (new DataProcessorApp())->registerCommands($this);

        // Или автоматическая загрузка всех приложений из директории
        $this->loadFromDirectory(__DIR__ . '/Applications');
    }
}
```

3. **Конфигурационный файл** (`config/console.php`):
```php
return [
    'applications' => [
        \App\Console\Applications\DataProcessorApp::class,
        \App\Console\Applications\ReportGeneratorApp::class
    ]
];
```

#### 5. Особенности разработки пользовательских приложений

**Что можно делать в консольных приложениях:**
* использовать DI‑контейнер для внедрения зависимостей;
* обращаться к моделям и ORM для работы с БД;
* использовать кэширование и логирование фреймворка;
* запускать асинхронные задачи;
* интегрироваться с внешними API.

**Пример с внедрением зависимостей:**
```php
class ReportGeneratorApp
{
    private DatabaseService $db;
    private FileSystem $fs;

    public function __construct(DatabaseService $db, FileSystem $fs)
    {
        $this->db = $db;
        $this->fs = $fs;
    }

    public function registerCommands(ConsoleKernel $kernel): void
    {
        $kernel->registerCommand(new class($this->db, $this->fs) extends BaseCommand {
            private DatabaseService $db;
            private FileSystem $fs;

            public function __construct(DatabaseService $db, FileSystem $fs)
            {
                $this->db = $db;
                $this->fs = $fs;
            }

            protected string $name = 'report:generate';
            protected string $description = 'Generate monthly reports';

            public function execute(array $arguments, array $options): int
            {
                $data = $this->db->fetchReportData();
                $this->fs->write('reports/monthly.pdf', $data);
                $this->success('Report generated!');
                return 0;
            }
        });
    }
}
```

#### 6. Инструменты для разработчиков

**Вспомогательные команды для создания приложений:**
* `make:console-app AppName` — создаёт заготовку консольного приложения в `app/Console/Applications/`;
* `make:command CommandName` — создаёт новую команду в `app/Console/Commands/`.

**Генерация шаблона приложения:**
```bash
php architect make:console-app DataProcessor
```
Создаст файл `app/Console/Applications/DataProcessorApp.php` с базовым шаблоном.

#### 7. Конфигурация и окружение

**Поддержка разных окружений:**
* команды могут проверять текущее окружение (`dev`, `prod`);
* возможность создания `.env.console` для консольных настроек;
* изоляция конфигураций CLI от веб‑части.

#### 8. Тестирование консольных приложений

**Рекомендации по тестированию:**
* юнит‑тесты для логики команд;
* интеграционные тесты с имитацией ввода/вывода;
* использование тестового ядра консоли в PHPUnit.

**Пример теста:**
```php
public function testSeedUsersCommand(): void
{
    $tester = new CommandTester(new SeedUsersCommand());
    $tester->execute(['count' => '5']);

    $this->assertStringContainsString('Successfully created 5 users!', $tester->getDisplay());
    $this->assertEquals(0, $tester->getStatusCode());
}
```

#### 9. Документация и справка

**Автогенерация документации:**
* команда `console:docs` создаёт Markdown‑файл со списком всех команд и их описанием;
* автоматическая генерация `--help` для каждой команды на основе методов `getDescription()`, `getArguments()`, `getOptions()`.

---

**Преимущества подхода:**
* **Модульность** — приложения и команды логически разделены.
* **Масштабируемость** — легко добавлять новые функции.
* **Повторное использование** — приложения можно переиспользовать в других проектах.
* **Стандартизация** — единый интерфейс для всех консольных утилит.
* **Интеграция** — полный доступ к возможностям Architect Framework.

### Интеграция дополнительных модулей из Composer в консольную систему Architect Framework

#### 1. Механизм обнаружения команд из сторонних пакетов

Модули, установленные через Composer, могут регистрировать свои консольные команды в Architect Framework. Для этого предлагается следующий механизм:

**Способ 1. Автоматическое обнаружение через Composer scripts**

В `composer.json` пакета добавляется скрипт, который регистрирует команды при установке:
```json
{
  "scripts": {
    "post-autoload-dump": "Architect\\Console\\CommandRegistry::registerFromPackage"
  }
}
```

**Способ 2. Сервис‑провайдеры**

Пакет предоставляет сервис‑провайдер, который регистрирует команды в ядре консоли:
```php
class ConsoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->make(ConsoleKernel::class)->registerCommands([
            \Vendor\Package\Commands\CustomCommand::class,
            \Vendor\Package\Commands\AnotherCommand::class
        ]);
    }
}
```

**Способ 3. Конфигурационный файл**

Пакет может объявлять команды в файле `composer.json`:
```json
{
  "extra": {
    "architect-console": {
      "commands": [
        "Vendor\\Package\\Commands\\CustomCommand",
        "Vendor\\Package\\Commands\\AnotherCommand"
      ]
    }
  }
}
```
Система при запуске сканирует все установленные пакеты на наличие секции `architect-console`.

#### 2. Процесс регистрации команд

**Алгоритм регистрации:**
1. При запуске консоли ядро (`ConsoleKernel`) сканирует:
   * директорию `app/Console/Commands/`;
   * пакеты в `vendor/` с секцией `architect-console` в `composer.json`.
2. Загружает классы команд через автозагрузчик Composer.
3. Регистрирует команды в `CommandRegistry`.
4. Кэширует список команд для ускорения последующих запусков.

**Пример команды из стороннего пакета:**
```php
<?php

namespace Vendor\DataExporter\Commands;

use Architect\Console\BaseCommand;

class ExportCommand extends BaseCommand
{
    protected string $name = 'export:data';
    protected string $description = 'Export data to CSV';

    public function getOptions(): array
    {
        return [
            ['--format', 'Output format (csv, json)']
        ];
    }

    public function execute(array $arguments, array $options): int
    {
        $format = $options['format'] ?? 'csv';
        // Логика экспорта данных
        $this->success("Data exported successfully in {$format} format!");
        return 0;
    }
}
```

#### 3. Установка и использование модулей

**Установка пакета с консольными командами:**
```bash
composer require vendor/data-exporter
```
После установки команды пакета становятся доступны в консоли Architect:
```bash
php architect export:data --format=json
```

**Примеры полезных пакетов:**
* `architect/migrations` — расширенные миграции БД;
* `vendor/cache-tools` — инструменты для работы с кэшем;
* `vendor/api-tester` — тестирование API из консоли;
* `vendor/data-importer` — импорт данных из разных источников.

#### 4. Разрешение конфликтов имён команд

**Стратегии разрешения конфликтов:**

1. **Приоритет пользовательских команд** — команды из `app/Console/Commands/` имеют высший приоритет.
2. **Пространства имён пакетов** — префикс имени команды по имени пакета:
   * `vendor:export:data` вместо `export:data`;
   * настраивается в `composer.json` пакета.
3. **Явная конфигурация** — в `config/console.php` можно переопределить или отключить команды:
```php
return [
    'commands' => [
        'export:data' => \App\Console\Commands\OverrideExportCommand::class // переопределение
    ],
    'disabled_commands' => [
        'vendor:export:data' // отключение
    ]
];
```
4. **Алиасы** — создание коротких имён для длинных команд:
```php
'aliases' => [
    'exp' => 'vendor:data-exporter:export'
]
```

#### 5. Кэширование списка команд

Для повышения производительности создаётся кэш зарегистрированных команд:

**Генерация кэша:**
```bash
php architect console:cache
```
**Очистка кэша:**
```bash
php architect console:clear-cache
```
Кэш хранится в `bootstrap/cache/console-commands.php`.

#### 6. Интеграция с DI‑контейнером

Команды из сторонних пакетов могут использовать внедрение зависимостей:
```php
class ExternalCommand extends BaseCommand
{
    private DatabaseService $db;

    public function __construct(DatabaseService $db)
    {
        parent::__construct();
        $this->db = $db;
    }

    // ...
}
```
DI‑контейнер автоматически внедряет зависимости при создании экземпляра команды.

#### 7. Тестирование сторонних команд

**Рекомендации по тестированию:**
* юнит‑тесты для логики команд;
* интеграционные тесты с имитацией ввода/вывода;
* проверка регистрации команд в ядре консоли.

**Пример теста для сторонней команды:**
```php
public function testExternalExportCommand(): void
{
    $tester = new CommandTester(new \Vendor\DataExporter\Commands\ExportCommand());
    $tester->execute(['--format' => 'json']);

    $this->assertStringContainsString('Data exported successfully', $tester->getDisplay());
    $this->assertEquals(0, $tester->getStatusCode());
}
```

#### 8. Документация и справка

**Автоматическое обновление справки:**
* команда `console:list` показывает все доступные команды, включая сторонние;
* `--help` для каждой команды генерируется автоматически на основе `getDescription()`, `getArguments()`, `getOptions()`.

**Генерация общей документации:**
```bash
php architect console:docs --format=markdown > docs/console-commands.md
```
Создаёт Markdown‑файл со списком всех команд и их описанием.

#### 9. Обновление и удаление модулей

**Обновление пакетов:**
```bash
composer update vendor/data-exporter
```
При обновлении кэш команд автоматически перестраивается.

**Удаление пакетов:**
```bash
composer remove vendor/data-exporter
```
Команды пакета автоматически удаляются из реестра.

#### 10. Лучшие практики для разработчиков пакетов

**Рекомендации при создании пакетов с консольными командами:**
* используйте префиксы имён команд по имени пакета (`vendor:command`);
* предоставляйте сервис‑провайдер для регистрации команд;
* документируйте все аргументы и опции;
* тестируйте команды в изоляции;
* указывайте совместимость с Architect Framework в `composer.json`;
* используйте семантическое версионирование.

**Пример `composer.json` для пакета с командами:**
```json
{
  "name": "vendor/data-exporter",
  "require": {
    "php": "^8.0",
    "architect/framework": "^2.0"
  },
  "autoload": {
    "psr-4": {
      "Vendor\\DataExporter\\": "src/"
    }
  },
  "extra": {
    "architect-console": {
      "commands": [
        "Vendor\\DataExporter\\Commands\\ExportCommand",
        "Vendor\\DataExporter\\Commands\\ImportCommand"
      ]
    }
  }
}
```

**Преимущества подхода:**
* **Расширяемость** — легко добавлять новые команды через Composer;
* **Изоляция** — пакеты не мешают друг другу;
* **Автоматизация** — регистрация команд без ручного вмешательства;
* **Производительность** — кэширование списка команд;
* **Стандартизация** — единый интерфейс для всех команд.
