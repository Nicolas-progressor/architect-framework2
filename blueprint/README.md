# Blueprint Template Engine

<div align="center">

**Версия 1.0.0**

Современный шаблонизатор для PHP 8.1+

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.1-blue)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

</div>

---

## Описание

Blueprint — модульный шаблонизатор с Blade/Twig-подобным синтаксисом, разработанный по принципам SOLID. Компилирует шаблоны в оптимизированный PHP-код с автоматическим экранированием вывода.

### Ключевые особенности

- **Полная DI-архитектура** — без static-методов и singleton'ов
- **Модульная структура** — Lexer, Parser, Compiler как независимые компоненты
- **Интерфейсы** — RuntimeInterface, FilterRegistryInterface, FunctionRegistryInterface
- **Компиляция в PHP** — максимальная производительность
- **Автоматическое экранирование** — защита от XSS по умолчанию
- **Наследование шаблонов** — блоки, extends, layout-система
- **40+ фильтров** — строковые, числовые, массивы, даты
- **Расширяемость** — регистрация собственных фильтров и функций

---

## Установка

```bash
composer require architect/blueprint
```

---

## Быстрый старт

```php
use Blueprint\Engine\Blueprint;
use Blueprint\Engine\Config\BlueprintConfig;

$config = new BlueprintConfig([
    'debug' => true,
    'cache_enabled' => false,
    'paths' => ['templates'],
]);

$blueprint = new Blueprint($config);

// Рендеринг строки
echo $blueprint->renderString('Привет, {{ name }}!', ['name' => 'Мир']);

// Рендеринг файла
echo $blueprint->render('page', ['title' => 'Главная']);
```

---

## Синтаксис

### Переменные

```blade
{{ name }}                    {# Экранированный вывод #}
{!! html !!}                  {# Без экранирования #}
{{ user.name }}               {# Свойство объекта #}
{{ user.address.city }}       {# Вложенные свойства #}
{{ items.0 }}                 {# Элемент массива #}
```

### Фильтры

```blade
{{ name | upper }}                    {# HELLO #}
{{ name | lower }}                    {# hello #}
{{ name | trim | capitalize }}        {# Цепочка фильтров #}
{{ text | truncate(50, '...') }}      {# С аргументами #}
{{ value | default('N/A') }}          {# Значение по умолчанию #}
{{ html | raw }}                      {# Без экранирования #}
```

### Управляющие конструкции

```blade
{% if condition %}
    ...
{% elseif other %}
    ...
{% else %}
    ...
{% endif %}

{% for item in items %}
    {{ loop.index }}: {{ item }}
{% endfor %}

{% foreach users as user %}
    {{ user.name }}
{% endforeach %}
```

### Наследование шаблонов

```blade
{# base.html #}
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}Сайт{% endblock %}</title>
</head>
<body>
    {% block content %}{% endblock %}
</body>
</html>

{# page.html #}
{% extends "base.html" %}

{% block title %}Страница{% endblock %}

{% block content %}
    <h1>Контент</h1>
{% endblock %}
```

### Layout-система

```blade
{# layout.html #}
<!DOCTYPE html>
<html>
<body>
    {% yield content %}
    {% yield sidebar %}
</body>
</html>

{# page.html #}
{% layout "layout.html" %}

{% section content %}
    Основной контент
{% endsection %}

{% section sidebar %}
    Боковая панель
{% endsection %}
```

---

## API

### Основные методы

```php
// Создание
$blueprint = new Blueprint(array|BlueprintConfig $config, ?object $container = null);

// Рендеринг
$blueprint->render(string $template, array $context = []): string;
$blueprint->renderString(string $source, array $context = []): string;

// Компиляция
$blueprint->compile(string $template): string;
$blueprint->compileString(string $source): string;

// Пути к шаблонам
$blueprint->addPath(string $path): void;
$blueprint->setPaths(array $paths): void;

// Глобальные переменные
$blueprint->addGlobal(string $key, mixed $value): void;
$blueprint->addGlobals(array $globals): void;

// Регистрация
$blueprint->registerFilter(string $name, callable $filter): void;
$blueprint->registerFunction(string $name, callable $function): void;

// Кеш
$blueprint->clearCache(): bool;
```

### Конфигурация

```php
$config = new BlueprintConfig([
    'debug' => true,                    // Режим отладки
    'show_errors' => true,              // Показывать ошибки
    'cache_enabled' => true,            // Включить кеш
    'cache_path' => 'cache/views',      // Путь к кешу
    'paths' => ['views'],               // Пути к шаблонам
    'extensions' => ['.blade.php'],     // Расширения файлов
]);
```

---

## Архитектура

```
blueprint/src/
├── Blueprint.php              # Главный класс
├── Compiler.php               # Компилятор (фасад)
├── Lexer.php                  # Лексер (фасад)
├── Parser.php                 # Парсер (фасад)
├── RuntimeFactory.php         # Фабрика Runtime
│
├── Lexer/                     # Модульный лексер
│   ├── TokenTypes.php         # Типы токенов
│   ├── Token.php              # Объект токена
│   ├── TokenStream.php        # Поток токенов
│   ├── TemplateTokenizer.php  # Токенизация шаблона
│   ├── ExpressionTokenizer.php# Токенизация выражений
│   ├── TagTokenizer.php       # Токенизация тегов
│   └── Lexer.php              # Координатор
│
├── Parser/                    # Модульный парсер
│   ├── ParserContext.php      # Контекст парсинга
│   ├── ExpressionParser.php   # Парсер выражений
│   ├── StatementParser.php    # Парсер statements
│   ├── BodyParser.php         # Парсер тела
│   ├── NodeFactory.php        # Фабрика узлов AST
│   └── Parser.php             # Координатор
│
├── Compiler/                  # Модульный компилятор
│   ├── ExpressionCompiler.php # Компиляция выражений
│   ├── StatementCompiler.php  # Компиляция statements
│   └── PhpGenerator.php       # Генерация PHP
│
├── Runtime/                   # Runtime среда
│   ├── Runtime.php            # Основной runtime
│   ├── Escaper.php            # Экранирование
│   ├── PropertyAccessor.php   # Доступ к свойствам
│   └── MethodCaller.php       # Вызов методов
│
├── Contracts/                 # Интерфейсы
│   ├── RuntimeInterface.php
│   ├── FilterRegistryInterface.php
│   └── FunctionRegistryInterface.php
│
├── Filters/                   # Фильтры
│   ├── FilterRegistry.php     # Реестр фильтров
│   ├── StringFilters.php
│   ├── ArrayFilters.php
│   ├── NumberFilters.php
│   ├── DateFilters.php
│   ├── ConversionFilters.php
│   └── TypeFilters.php
│
├── Functions/                 # Функции
│   ├── FunctionRegistry.php   # Реестр функций
│   ├── StringFunctions.php
│   ├── ArrayFunctions.php
│   ├── MathFunctions.php
│   ├── DateFunctions.php
│   ├── UrlFunctions.php
│   └── DebugFunctions.php
│
└── Integrations/              # Интеграции
    └── Architect/
        ├── BlueprintServiceProvider.php
        └── BlueprintBootstrap.php
```

---

## Встроенные фильтры

### Строковые

| Фильтр | Описание | Пример |
|--------|----------|--------|
| `upper` | Верхний регистр | `{{ name \| upper }}` |
| `lower` | Нижний регистр | `{{ name \| lower }}` |
| `capitalize` | Первая буква заглавная | `{{ name \| capitalize }}` |
| `trim` | Удалить пробелы | `{{ name \| trim }}` |
| `truncate(n, suffix)` | Обрезать | `{{ text \| truncate(50) }}` |
| `replace(search, replace)` | Замена | `{{ text \| replace('a', 'b') }}` |
| `escape`, `e` | HTML экранирование | `{{ html \| e }}` |
| `striptags` | Удалить HTML-теги | `{{ html \| striptags }}` |

### Массивы

| Фильтр | Описание | Пример |
|--------|----------|--------|
| `length` | Длина | `{{ items \| length }}` |
| `first` | Первый элемент | `{{ items \| first }}` |
| `last` | Последний элемент | `{{ items \| last }}` |
| `join(separator)` | Объединить | `{{ items \| join(', ') }}` |
| `sort` | Сортировка | `{{ items \| sort }}` |
| `reverse` | Реверс | `{{ items \| reverse }}` |
| `slice(start, length)` | Срез | `{{ items \| slice(0, 5) }}` |

### Числа

| Фильтр | Описание | Пример |
|--------|----------|--------|
| `abs` | Модуль | `{{ num \| abs }}` |
| `round(precision)` | Округление | `{{ num \| round(2) }}` |
| `floor` | Округление вниз | `{{ num \| floor }}` |
| `ceil` | Округление вверх | `{{ num \| ceil }}` |

### Даты

| Фильтр | Описание | Пример |
|--------|----------|--------|
| `date(format)` | Формат даты | `{{ timestamp \| date('d.m.Y') }}` |

### Прочие

| Фильтр | Описание | Пример |
|--------|----------|--------|
| `default(value)` | По умолчанию | `{{ name \| default('Гость') }}` |
| `raw` | Без экранирования | `{{ html \| raw }}` |
| `json` | В JSON | `{{ data \| json }}` |

---

## Расширение

### Пользовательские фильтры

```php
$blueprint->registerFilter('rot13', function($value) {
    return str_rot13($value);
});

// Использование: {{ text | rot13 }}
```

### Пользовательские функции

```php
$blueprint->registerFunction('asset', function($path) {
    return '/assets/' . $path;
});

// Использование: {{ asset('css/style.css') }}
```

---

## Тестирование

```bash
php blueprint/tests/run.php
```

---

## Документация

- [Установка и настройка](docs/installation.md)
- [Синтаксис шаблонов](docs/syntax.md)
- [Фильтры](docs/filters.md)
- [Функции](docs/functions.md)
- [Наследование шаблонов](docs/inheritance.md)
- [Layout система](docs/layout.md)
- [Элементы и виджеты](docs/elements.md)
- [Расширение Blueprint](docs/extending.md)
- [API Reference](docs/api.md)
- [Интеграция](docs/integrations.md)

---

## Требования

- PHP 8.1+
- Composer

---

## Лицензия

MIT License
