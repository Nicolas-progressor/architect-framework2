# Консольные команды (Console)

Компонент Console предоставляет интерфейс командной строки (CLI) для управления приложением: выполнение миграций, генерация кода, очистка кэша, запуск задач по расписанию и т.д. Architect RED 2 использует симфонический Console Component с собственными командами и возможностью расширения.

## Исполняемый файл

Основной скрипт для запуска консольных команд – `bin/arc`. Он автоматически загружает контейнер зависимостей и регистрирует доступные команды.

```bash
php bin/arc
```

Вывод списка команд:

```
Architect RED 2 Console Tool

Usage:
  command [options] [arguments]

Available commands:
  help                        Display help for a command
  list                        List commands
  app:create                  Create a new application
  module:create               Create a new module
  controller:create           Create a new controller
  model:create                Create a new model
  migration:create            Create a new migration
  migration:run               Run pending migrations
  migration:rollback          Rollback the last migration
  migration:status            Show migration status
  cache:clear                 Clear application cache
  route:list                  List all registered routes
  service:list                List registered services
  debug:panel                 Enable/disable debug panel
  env:show                    Show current environment configuration
```

## Встроенные команды

### Управление приложениями

- `app:create <name>` – создать новое приложение в папке `app/apps/<name>/`.

### Управление модулями

- `module:create <name>` – создать модуль в `app/modules/<name>/` с базовой структурой (controller, model, view).

### Генерация кода

- `controller:create <name>` – создать контроллер в текущем модуле (или указать модуль через `--module`).
- `model:create <name>` – создать модель.
- `migration:create <name>` – создать миграцию базы данных.

### Миграции базы данных

- `migration:run` – выполнить все неприменённые миграции.
- `migration:rollback` – откатить последнюю миграцию.
- `migration:status` – показать статус миграций.

### Кэш

- `cache:clear` – очистить кэш шаблонов, конфигурации и других данных.

### Отладка

- `debug:panel` – включить/выключить Debug Panel (требует прав администратора).
- `env:show` – вывести текущую конфигурацию окружения.

### Информация

- `route:list` – вывести список всех зарегистрированных маршрутов.
- `service:list` – вывести список сервисов в контейнере.

## Создание собственной команды

### Структура команды

Команда – это класс, расширяющий `Symfony\Component\Console\Command\Command`. Он должен быть зарегистрирован в контейнере с тегом `console.command`.

Пример команды:

```php
namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HelloCommand extends Command
{
    protected static $defaultName = 'app:hello';
    protected static $defaultDescription = 'Say hello';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hello, world!');
        return Command::SUCCESS;
    }
}
```

### Регистрация команды

Добавьте команду в контейнер с тегом `console.command`:

```php
// в сервис-провайдере
$this->container->set('console.command.hello', HelloCommand::class);
$this->container->tag('console.command', ['console.command.hello']);
```

Или используйте автоматическое обнаружение, если команда находится в определённом namespace (настраивается в конфигурации).

## Конфигурация консоли

Настройки консольного компонента находятся в `app/config/console.json`:

```json
{
    "name": "Architect RED 2 Console",
    "version": "1.0.0",
    "auto_discover_commands": true,
    "command_namespaces": [
        "App\\Console\\Commands",
        "Architect\\Console\\Commands"
    ]
}
```

## Интеграция с контейнером зависимостей

Консольные команды получают доступ к контейнеру через `$this->getContainer()` (если команда расширяет `ContainerAwareCommand`). Вы можете внедрять сервисы в конструктор команды, так как контейнер разрешает зависимости автоматически.

## Запуск команд по расписанию (Cron)

Architect не включает встроенный планировщик задач (scheduler), но вы можете использовать системный cron для запуска команд.

Пример crontab:

```cron
* * * * * cd /path/to/project && php bin/arc app:daily-task >> /var/log/cron.log 2>&1
```

Для более сложного планирования можно использовать библиотеку `symfony/scheduler` или `laravel/scheduler` (при интеграции).

## Тестирование команд

Консольные команды можно тестировать с помощью `Symfony\Component\Console\Tester\CommandTester`.

```php
use Symfony\Component\Console\Tester\CommandTester;

$command = $container->get('console.command.hello');
$tester = new CommandTester($command);
$tester->execute([]);
$this->assertStringContainsString('Hello', $tester->getDisplay());
```

## Примеры

### Команда для отправки email

```php
class SendNewsletterCommand extends Command
{
    private Mailer $mailer;

    public function __construct(Mailer $mailer)
    {
        parent::__construct();
        $this->mailer = $mailer;
    }

    protected function configure()
    {
        $this->setName('app:send-newsletter')
            ->setDescription('Send newsletter to all subscribers');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Sending newsletters...');
        $this->mailer->sendToAll();
        $output->writeln('Done.');
        return Command::SUCCESS;
    }
}
```

### Команда с аргументами и опциями

```php
protected function configure()
{
    $this->setName('user:create')
        ->setDescription('Create a new user')
        ->addArgument('email', InputArgument::REQUIRED, 'User email')
        ->addOption('admin', null, InputOption::VALUE_NONE, 'Make user admin');
}

protected function execute(InputInterface $input, OutputInterface $output)
{
    $email = $input->getArgument('email');
    $isAdmin = $input->getOption('admin');
    // создание пользователя
    $output->writeln("User $email created.");
}
```

Запуск:

```bash
php bin/arc user:create john@example.com --admin
```

## Расширение консоли

### Добавление собственного набора команд

Создайте папку `src/Console/Commands/` в своём пакете и зарегистрируйте команды через сервис-провайдер.

### Кастомизация вывода

Вы можете изменить стиль вывода, используя `OutputFormatterStyle`:

```php
$output->getFormatter()->setStyle('success', new OutputFormatterStyle('green'));
$output->writeln('<success>Operation successful</success>');
```

## Заключение

Компонент Console предоставляет мощный и гибкий инструмент для управления приложением через командную строку. Использование консольных команд упрощает автоматизацию рутинных задач, развёртывание и отладку.

Дополнительные сведения см. в [документации по консольным командам](../docs2/console.md).