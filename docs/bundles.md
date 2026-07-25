# Система бандлов Architect Framework

## Введение

Система бандлов Architect Framework вдохновлена Symfony и предоставляет модульный подход к организации кода приложения. Каждый бандл представляет собой структурированный пакет функциональности, который может включать в себя контроллеры, модели, представления, конфигурации, сервисы и другие компоненты.

## Основные компоненты

### 1. Интерфейс BundleInterface

Каждый бандл должен реализовывать интерфейс `Architect\Contracts\BundleInterface`:

```php
interface BundleInterface
{
    public function getName(): string;
    public function register(ContainerInterface $container): void;
    public function boot(ContainerInterface $container, FrameworkInterface $framework): void;
    public function shutdown(ContainerInterface $container, FrameworkInterface $framework): void;
}
```

### 2. Абстрактный класс AbstractBundle

Для упрощения создания бандлов предоставляется абстрактный класс `Architect\Support\AbstractBundle`:

```php
abstract class AbstractBundle implements BundleInterface
{
    public function getName(): string
    {
        // Автоматическое определение имени из имени класса
    }
    
    // Остальные методы имеют пустые реализации по умолчанию
}
```

## Создание бандла

### Структура бандла

```
src/
├── Bundle/
│   ├── MyBundle/
│   │   ├── Controller/           # Контроллеры
│   │   ├── Model/               # Модели
│   │   ├── View/                # Представления
│   │   ├── Resources/
│   │   │   ├── config/          # Конфигурация
│   │   │   ├── views/           # Шаблоны
│   │   │   ├── translations/    # Переводы
│   │   │   └── public/          # Статические файлы
│   │   ├── ServiceProvider/     # Сервис-провайдеры
│   │   ├── Commands/            # Консольные команды
│   │   ├── Migrations/          # Миграции базы данных
│   │   ├── MyBundle.php         # Основной класс бандла
│   │   └── composer.json        # Конфигурация Composer
```

### Пример бандла

```php
<?php

namespace App\Bundle\MyBundle;

use Architect\Support\AbstractBundle;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Core\Contracts\FrameworkInterface;

class MyBundle extends AbstractBundle
{
    public function register(ContainerInterface $container): void
    {
        // Регистрация сервисов бандла
        $container->singleton('mybundle.service', fn() => new MyService());
    }
    
    public function boot(ContainerInterface $container, FrameworkInterface $framework): void
    {
        // Инициализация бандла после регистрации всех сервисов
    }
}
```

## Регистрация бандлов

### Автоматическое обнаружение

Бандлы автоматически обнаруживаются через Composer. Добавьте бандл в `composer.json`:

```json
{
    "extra": {
        "architect": {
            "bundles": [
                "App\\Bundle\\MyBundle\\MyBundle"
            ]
        }
    }
}
```

### Ручная регистрация

```php
$framework = new Framework($container, $statement);
$framework->registerBundle(new MyBundle());
```

## Конфигурация бандлов

### Конфигурационные файлы

Бандлы могут иметь собственную конфигурацию в формате JSON:

1. `Resources/config/config.json` - основная конфигурация бандла
2. `app/config/bundles/{bundleName}.json` - переопределение конфигурации в приложении
3. `app/config/bundles/{bundleName}.{env}.json` - конфигурация для конкретного окружения

### Загрузка конфигурации

```php
use Architect\Core\Bundle\Config\BundleConfigLoader;

$loader = new BundleConfigLoader();
$config = $loader->load($bundle, $container);
```

## Сервис-провайдеры бандлов

Бандлы могут содержать сервис-провайдеры для регистрации сервисов:

```php
<?php

namespace App\Bundle\MyBundle\ServiceProvider;

use Architect\Support\AbstractServiceProvider;
use Architect\Core\Contracts\ContainerInterface;

class MyServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton('mybundle.service', fn() => new MyService());
    }
}
```

## Маршрутизация

### Конфигурация маршрутов

Бандлы могут определять маршруты в JSON файлах:

```json
{
    "home": {
        "path": "/",
        "controller": "home",
        "action": "index",
        "methods": ["GET"]
    }
}
```

### Аннотации маршрутов

```php
<?php

namespace App\Bundle\MyBundle\Controller;

/**
 * @Route("/mybundle")
 */
class MyController
{
    /**
     * @Route("/", name="mybundle.index", methods={"GET"})
     */
    public function index()
    {
        // ...
    }
}
```

## Представления и шаблоны

### Структура представлений

```
Resources/views/
├── layout.blu
├── home/
│   └── index.blu
└── partials/
    └── header.blu
```

### Использование представлений бандлов

```php
use Architect\Core\Bundle\View\BundleViewLoader;

$loader = new BundleViewLoader();
$html = $loader->render($bundle, 'home/index', ['data' => $data]);
```

## Статические файлы

### Публикация ассетов

```php
use Architect\Core\Bundle\Asset\AssetPublisher;

$publisher = new AssetPublisher();
$published = $publisher->publish($bundle);
```

### Использование ассетов

После публикации ассеты доступны по адресу:
```
/htdocs/assets/bundles/{bundleName}/
```

## Консольные команды

### Создание команд

```php
<?php

namespace App\Bundle\MyBundle\Commands;

use Architect\Services\Console\BaseCommand;

class MyCommand extends BaseCommand
{
    protected $name = 'mybundle:command';
    protected $description = 'My bundle command';
    
    public function handle()
    {
        $this->info('Command executed!');
    }
}
```

### Автоматическая регистрация

Команды автоматически обнаруживаются в директориях:
- `Commands/`
- `Console/Commands/`
- `Command/`

## Миграции базы данных

### Создание миграций

```php
<?php

namespace App\Bundle\MyBundle\Migrations;

use Axiom\Migration\Migration;

class CreateMyTable extends Migration
{
    public function up()
    {
        $this->table('my_table')
            ->addColumn('name', 'string')
            ->addColumn('created_at', 'datetime')
            ->create();
    }
    
    public function down()
    {
        $this->table('my_table')->drop();
    }
}
```

### Публикация миграций

```php
use Architect\Core\Bundle\Migration\BundleMigrationManager;

$manager = new BundleMigrationManager();
$copied = $manager->publishMigrations($bundle);
```

## Интеграция с фреймворком

### Жизненный цикл бандла

1. **Обнаружение** - автоматическое обнаружение через Composer
2. **Регистрация** - вызов метода `register()` для регистрации сервисов
3. **Загрузка конфигурации** - загрузка конфигурации бандла
4. **Регистрация сервис-провайдеров** - регистрация сервис-провайдеров бандла
5. **Регистрация маршрутов** - загрузка маршрутов бандла
6. **Регистрация представлений** - регистрация шаблонов бандла
7. **Запуск** - вызов метода `boot()` для инициализации бандла
8. **Завершение** - вызов метода `shutdown()` при завершении работы

### Расширение Framework

Класс `Framework` был расширен для поддержки бандлов:

```php
class Framework implements FrameworkInterface
{
    private BundleManager $bundleManager;
    
    public function getBundleManager(): BundleManager;
    public function registerBundle(BundleInterface $bundle): void;
    public function registerBundlesFromDiscovery(): void;
    public function registerBundleServices(): void;
    public function bootBundles(): void;
}
```

## Пример использования

### Полный пример бандла

```php
<?php

namespace App\Bundle\BlogBundle;

use Architect\Support\AbstractBundle;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Core\Contracts\FrameworkInterface;

class BlogBundle extends AbstractBundle
{
    public function register(ContainerInterface $container): void
    {
        // Регистрация сервисов
        $container->singleton('blog.post_repository', fn() => new PostRepository());
        $container->singleton('blog.comment_repository', fn() => new CommentRepository());
        
        // Регистрация сервис-провайдеров
        $container->singleton('blog.service_provider', fn() => new BlogServiceProvider());
    }
    
    public function boot(ContainerInterface $container, FrameworkInterface $framework): void
    {
        // Инициализация бандла
        $postRepository = $container->get('blog.post_repository');
        $postRepository->initialize();
    }
}
```

### Использование в приложении

```php
// bootstrap.php
$framework = new Framework($container, $statement);

// Автоматическая регистрация бандлов
$framework->registerBundlesFromDiscovery();

// Регистрация сервисов бандлов
$framework->registerBundleServices();

// Запуск бандлов
$framework->bootBundles();

// Запуск приложения
$framework->run();
```

## Команды CLI

### Кэширование бандлов

```bash
php bin/arc bundle:cache
```

### Публикация ассетов

```bash
php bin/arc bundle:publish
```

### Публикация миграций

```bash
php bin/arc bundle:publish-migrations
```

### Список зарегистрированных бандлов

```bash
php bin/arc bundle:list
```

## Лучшие практики

1. **Именование** - используйте суффикс `Bundle` для классов бандлов
2. **Изоляция** - бандлы должны быть максимально независимыми
3. **Конфигурация** - предоставляйте значения по умолчанию для всех настроек
4. **Зависимости** - явно объявляйте зависимости в composer.json
5. **Документация** - документируйте публичный API бандла
6. **Тестирование** - включайте тесты в состав бандла

## Расширение системы

Система бандлов может быть расширена через:

1. **События** - добавление событий жизненного цикла бандлов
2. **Метаданные** - добавление метаданных для бандлов
3. **Зависимости** - поддержка зависимостей между бандлами
4. **Переопределение** - механизм переопределения компонентов бандлов

## Заключение

Система бандлов Architect Framework предоставляет мощный и гибкий механизм для создания модульных приложений. Она сочетает в себе лучшие практики Symfony с архитектурой Architect, обеспечивая простоту использования и расширяемость.