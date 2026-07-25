# Установка и настройка

## Установка через Composer

```bash
composer require architect/blueprint
```

---

## Базовая настройка

```php
use Blueprint\Engine\Blueprint;
use Blueprint\Engine\Config\BlueprintConfig;

$config = new BlueprintConfig([
    'debug' => true,
    'cache_enabled' => false,
]);

$blueprint = new Blueprint($config);
```

---

## Полная конфигурация

```php
$config = new BlueprintConfig([
    // Режим отладки
    'debug' => true,
    
    // Показывать ошибки в шаблонах
    'show_errors' => true,
    
    // Включить кеширование скомпилированных шаблонов
    'cache_enabled' => true,
    
    // Путь к директории кеша
    'cache_path' => __DIR__ . '/cache/views',
    
    // Пути к шаблонам (можно несколько)
    'paths' => [
        __DIR__ . '/templates',
        __DIR__ . '/views',
    ],
    
    // Расширения файлов шаблонов
    'extensions' => ['.blade.php', '.html'],
    
    // Автоматическое экранирование вывода
    'autoescape' => true,
    
    // Строгий режим (ошибка при отсутствующей переменной)
    'strict_variables' => false,
]);

$blueprint = new Blueprint($config);
```

---

## Методы конфигурации

### Пути к шаблонам

```php
// Добавить путь
$blueprint->addPath('/path/to/templates');

// Установить несколько путей
$blueprint->setPaths([
    '/path/to/templates',
    '/another/path',
]);

// Добавить расширение файла
$blueprint->addExtension('.tpl');
```

### Глобальные переменные

```php
// Добавить одну переменную
$blueprint->addGlobal('siteName', 'My Site');
$blueprint->addGlobal('version', '1.0.0');

// Добавить несколько переменных
$blueprint->addGlobals([
    'siteName' => 'My Site',
    'version' => '1.0.0',
]);

// Получить все глобальные переменные
$globals = $blueprint->getGlobals();

// Очистить глобальные переменные
$blueprint->clearGlobals();
```

---

## DI-контейнер

Blueprint поддерживает интеграцию с DI-контейнером:

```php
// Передача контейнера в конструктор
$blueprint = new Blueprint($config, $container);

// Или позже
$blueprint->setContainer($container);

// Получение контейнера
$container = $blueprint->getContainer();
```

---

## Структура директорий

```
project/
├── templates/           # Шаблоны
│   ├── base.blade.php
│   ├── page.blade.php
│   └── partials/
│       ├── header.blade.php
│       └── footer.blade.php
├── cache/
│   └── views/           # Скомпилированные шаблоны
└── config/
    └── blueprint.json   # Конфигурация (опционально)
```

---

## Режимы работы

### Development

```php
$config = new BlueprintConfig([
    'debug' => true,
    'cache_enabled' => false,
    'show_errors' => true,
]);
```

### Production

```php
$config = new BlueprintConfig([
    'debug' => false,
    'cache_enabled' => true,
    'show_errors' => false,
]);
```

---

## Управление кешем

```php
// Очистить весь кеш
$blueprint->clearCache();

// Проверить существование шаблона
if ($blueprint->exists('page')) {
    echo $blueprint->render('page');
}
```

---

## Рендеринг

### Из файла

```php
echo $blueprint->render('page', [
    'title' => 'Заголовок',
    'content' => 'Содержимое',
]);
```

### Из строки

```php
$html = $blueprint->renderString('Привет, {{ name }}!', [
    'name' => 'Мир'
]);
// Результат: "Привет, Мир!"
```

### Компиляция без рендеринга

```php
// Получить скомпилированный PHP-код
$php = $blueprint->compile('template');

// Компиляция строки
$php = $blueprint->compileString('{{ name | upper }}');
```

---

## Обработка ошибок

При включённом `show_errors` ошибки шаблона отображаются с подробной информацией:

```php
$config = new BlueprintConfig([
    'debug' => true,
    'show_errors' => true,
]);
```

В production режиме ошибки логируются, но не отображаются:

```php
$config = new BlueprintConfig([
    'debug' => false,
    'show_errors' => false,
]);
```
