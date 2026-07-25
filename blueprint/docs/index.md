# Blueprint Template Engine

> Модульный шаблонизатор для PHP 8.1+ с DI-архитектурой

---

## Оглавление

| Документ | Описание |
|----------|----------|
| [Установка](installation.md) | Установка и конфигурация |
| [Синтаксис](syntax.md) | Синтаксис шаблонов |
| [Фильтры](filters.md) | Встроенные фильтры |
| [Функции](functions.md) | Встроенные функции |
| [Наследование](inheritance.md) | Наследование шаблонов |
| [Layout](layout.md) | Layout-система |
| [Элементы](elements.md) | Элементы и виджеты |
| [Расширение](extending.md) | Создание фильтров и функций |
| [API](api.md) | API Reference |
| [Интеграция](integrations.md) | Интеграция с фреймворками |

---

## Быстрый старт

```php
use Blueprint\Engine\Blueprint;
use Blueprint\Engine\Config\BlueprintConfig;

$config = new BlueprintConfig(['debug' => true]);
$blueprint = new Blueprint($config);

echo $blueprint->renderString('Привет, {{ name }}!', ['name' => 'Мир']);
```

---

## Основные возможности

### Переменные и фильтры

```blade
{{ user.name | upper }}
{{ price | number_format(2) }}
{{ content | truncate(100, '...') }}
```

### Управляющие конструкции

```blade
{% if user.isActive %}
    Добро пожаловать!
{% endif %}

{% for item in items %}
    {{ loop.index }}: {{ item.name }}
{% endfor %}
```

### Наследование шаблонов

```blade
{% extends "base.html" %}
{% block title %}Страница{% endblock %}
{% block content %}Контент{% endblock %}
```

---

## Архитектура

Blueprint построен по принципам SOLID:

- **SRP** — каждый компонент отвечает за одну задачу
- **OCP** — расширение через регистрацию фильтров/функций
- **LSP** — интерфейсы для всех основных компонентов
- **ISP** — разделённые интерфейсы
- **DIP** — DI через конструктор, без static/singleton

### Компоненты

| Компонент | Назначение |
|-----------|------------|
| **Lexer** | Токенизация исходного кода |
| **Parser** | Построение AST из токенов |
| **Compiler** | Генерация PHP из AST |
| **Runtime** | Выполнение скомпилированного кода |
| **FilterRegistry** | Управление фильтрами |
| **FunctionRegistry** | Управление функциями |

---

## Лицензия

MIT License
