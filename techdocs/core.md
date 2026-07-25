# Ядро фреймворка

Ядро Architect RED 2 состоит из четырёх основных классов: `Container`, `Statement`, `Framework` и `EnvironmentManager`. Эти классы управляют жизненным циклом приложения, зависимостями и конфигурацией.

## Container (Контейнер зависимостей)

**Файл:** `architect/Core/Container.php`

Контейнер реализует паттерн Singleton и управляет сервисами приложения. Он поддерживает три типа регистрации:

1. **Инстансы** — готовые объекты, установленные через `set()`.
2. **Фабрики** — callable-функции, создающие объект при первом обращении.
3. **Привязки** — имена классов, которые будут инстанциированы с передачей контейнера в конструктор.

### Основные методы

```php
$container = new Container();

// Установить готовый инстанс
$container->set('service', $instance);

// Зарегистрировать фабрику
$container->factory('service', function(Container $c) {
    return new Service($c->get('dependency'));
});

// Зарегистрировать привязку класса
$container->bind('service', Service::class);

// Получить сервис (синглтон)
$service = $container->get('service');

// Проверить наличие
if ($container->has('service')) {
    // ...
}

// Зарегистрировать callback после разрешения
$container->afterResolving('service', function($service) {
    $service->init();
});
```

### Разрешение зависимостей

При вызове `get()` контейнер выполняет следующие шаги:

1. Проверяет, есть ли уже созданный инстанс.
2. Если нет, проверяет наличие фабрики и вызывает её.
3. Если нет фабрики, проверяет привязку класса и создаёт объект через `new $class($container)`.
4. Если ничего не найдено, выбрасывает исключение `RuntimeException`.

Все сервисы, созданные через фабрику или привязку, кэшируются как синглтоны.

## Statement (Менеджер statement-ов)

**Файл:** `architect/Core/Statement.php`

Statement-ы представляют собой этапы жизненного цикла приложения. Каждый statement — это именованный этап, к которому можно привязать callback-функции. Statement-ы выполняются в строгом порядке.

### Предопределённые statement-ы

| Statement | Описание |
|-----------|----------|
| `core_preinit` | Предварительная инициализация (до загрузки конфигурации) |
| `core_init` | Инициализация ядра, регистрация сервисов |
| `core_load` | Загрузка приложения, роутинг |
| `core_post_load` | После загрузки |
| `app_load` | Загрузка данных модуля |
| `app_data` | Обработка данных (модель) |
| `app_output` | Вывод (контроллер) |
| `render` | Рендеринг представления |

### Использование

```php
$statement = new Statement($container);

// Добавить callback с приоритетом (меньше = раньше)
$statement->on('core_init', function(Container $c) {
    echo "Инициализация ядра";
}, 10);

$statement->on('core_init', function(Container $c) {
    echo "Второй callback";
}, 20);

// Выполнить конкретный statement
$statement->run('core_init');

// Выполнить все statement-ы по порядку
$statement->runAll();
```

### Интеграция с ServiceProvider

`ServiceProvider` автоматически регистрирует обработчики для statement-ов через метод `configureStatements()`. Это позволяет сервисам выполнять инициализацию на нужных этапах.

## Framework (Основной класс приложения)

**Файл:** `architect/Core/Framework.php`

Класс `Framework` является центральной точкой приложения. Он координирует работу контейнера и statement-ов, а также предоставляет методы для загрузки сервисов.

### Основные методы

```php
$framework = new Framework($container, $statement);

// Получить контейнер
$container = $framework->getContainer();

// Получить statement менеджер
$statement = $framework->getStatement();

// Загрузить сервис (вызовет метод boot() если есть)
$framework->boot('router');

// Загрузить несколько сервисов
$framework->bootAll(['router', 'config', 'debug']);

// Запустить приложение
$framework->run();
```

Метод `run()` вызывает `$statement->runAll()`, что запускает весь жизненный цикл приложения.

## EnvironmentManager (Менеджер окружения)

**Файл:** `architect/Core/EnvironmentManager.php`

`EnvironmentManager` определяет текущее окружение (development, testing, staging, production) и загружает соответствующую конфигурацию. Он реализует интерфейс `EnvironmentInterface`.

### Определение окружения

Окружение определяется по приоритетной цепочке:

1. **Переменная окружения ОС** `APP_ENV`
2. **Файл `.env`** в корне проекта
3. **Константа PHP** `APP_ENV`
4. **Значение по умолчанию** — `production`

### Загрузка конфигурации

Конфигурация загружается из двух источников:

1. **Общий конфиг** — `app/config/config.json`
2. **Окружение** — `app/config/environment/{env}.json`

Настройки окружения переопределяют общие настройки (рекурсивное слияние).

### API

```php
$env = new EnvironmentManager();

// Получить название окружения
$envName = $env->getEnvironment(); // 'development'

// Проверить окружение
$env->isDevelopment(); // true/false
$env->isTesting();     // true/false
$env->isStaging();     // true/false
$env->isProduction();  // true/false

// Получить настройку с dot notation
$value = $env->get('database.host', 'localhost');

// Получить все настройки
$config = $env->all();

// Проверить, загружена ли конфигурация
$loaded = $env->isConfigLoaded();
```

### Константы

После инициализации EnvironmentManager определяет две глобальные константы:

- `APP_ENV` — текущее окружение.
- `APP_DEBUG` — true, если окружение development или testing.

## Взаимодействие компонентов ядра

1. **Bootstrap** создаёт экземпляр `EnvironmentManager`.
2. Создаётся `Container`, в него регистрируется `EnvironmentManager`.
3. Создаётся `Statement` с передачей контейнера.
4. Создаётся `Framework` с контейнером и statement.
5. `ServiceProvider` регистрирует сервисы в контейнере и настраивает statement-ы.
6. Вызывается `$framework->run()`, который запускает statement-ы.

## Расширение ядра

### Добавление нового statement

```php
$statement->on('custom_statement', function(Container $c) {
    // логика
});
```

### Создание собственного сервиса

```php
class MyService {
    public function __construct(Container $container) {
        // инъекция зависимостей
    }
    
    public function boot() {
        // вызывается при framework->boot()
    }
}

// Регистрация в ServiceProvider
$container->factory('my_service', fn($c) => new MyService($c));
```

### Переопределение стандартных классов

Вы можете заменить любой класс ядра, создав свой класс и зарегистрировав его в контейнере до вызова `Framework::run()`. Однако это требует глубокого понимания внутренних процессов.

## Отладка

Ядро интегрировано с отладочной панелью. В statement `render` добавляется вывод панели (если не API-запрос). Вы можете отключить панель через конфигурацию `debug.json`.

## Заключение

Ядро Architect RED 2 предоставляет мощный и гибкий фундамент для построения веб-приложений. Благодаря чёткому разделению ответственности (Container, Statement, Framework, EnvironmentManager) и поддержке dependency injection фреймворк легко расширять и адаптировать под конкретные задачи.