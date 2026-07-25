# Layout система

Blueprint поддерживает альтернативную систему шаблонов через layout и sections.

---

## Основные концепции

| Тег | Описание |
|-----|----------|
| `{% layout "name" %}` | Указывает layout-шаблон |
| `{% section name %}` | Определяет секцию |
| `{% yield name %}` | Выводит секцию в layout |

---

## Layout-шаблон

**layouts/main.blade.php:**

```blade
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>{% yield title %}</title>
    <link href="/css/app.css" rel="stylesheet">
</head>
<body>
    <header>
        {% include 'partials/header' %}
    </header>
    
    <div class="container">
        <main>
            {% yield content %}
        </main>
        
        <aside>
            {% yield sidebar %}
        </aside>
    </div>
    
    <footer>
        {% yield footer %}
        {% include 'partials/footer' %}
    </footer>
    
    <script src="/js/app.js"></script>
</body>
</html>
```

---

## Страница с layout

**pages/home.blade.php:**

```blade
{% layout "layouts/main.blade.php" %}

{% section title %}
    Главная страница
{% endsection %}

{% section content %}
    <h1>Добро пожаловать!</h1>
    <p>Содержимое главной страницы.</p>
{% endsection %}

{% section sidebar %}
    <h3>Новости</h3>
    <ul>
        <li>Новость 1</li>
        <li>Новость 2</li>
    </ul>
{% endsection %}

{% section footer %}
    <p>Дополнительная информация в футере.</p>
{% endsection %}
```

---

## Отличия от extends/block

| extends/block | layout/section |
|---------------|----------------|
| `{% extends "base" %}` | `{% layout "base" %}` |
| `{% block content %}` | `{% section content %}` |
| `{% endblock %}` | `{% endsection %}` |
| `{{ parent() }}` | Нет аналога |
| Вложенное наследование | Одноуровневый layout |

---

## Layout с значениями по умолчанию

```blade
{# layouts/default.blade.php #}

<title>
    {% yield title %}
    {% if not yield('title') %}
        Сайт по умолчанию
    {% endif %}
</title>

<main>
    {% yield content %}
    {% if not yield('content') %}
        <p>Контент не найден.</p>
    {% endif %}
</main>
```

---

## Несколько layout-ов

### Админка

**layouts/admin.blade.php:**

```blade
<!DOCTYPE html>
<html>
<head>
    <title>{% yield title %} - Админка</title>
    <link href="/css/admin.css" rel="stylesheet">
</head>
<body class="admin">
    <nav class="admin-nav">
        {% include 'admin/partials/nav' %}
    </nav>
    
    <main>
        {% yield content %}
    </main>
</body>
</html>
```

### Публичная часть

**layouts/public.blade.php:**

```blade
<!DOCTYPE html>
<html>
<head>
    <title>{% yield title %}</title>
    <link href="/css/app.css" rel="stylesheet">
</head>
<body>
    <header>
        {% include 'partials/header' %}
    </header>
    
    <main>
        {% yield content %}
    </main>
    
    <footer>
        {% include 'partials/footer' %}
    </footer>
</body>
</html>
```

---

## Структура проекта

```
templates/
├── layouts/
│   ├── main.blade.php      # Основной layout
│   ├── full.blade.php      # На всю ширину
│   ├── admin.blade.php     # Админка
│   └── email.blade.php     # Email-шаблоны
│
├── pages/
│   ├── home.blade.php
│   ├── about.blade.php
│   └── contact.blade.php
│
├── admin/
│   ├── dashboard.blade.php
│   └── users.blade.php
│
└── partials/
    ├── header.blade.php
    ├── footer.blade.php
    └── sidebar.blade.php
```

---

## Динамический выбор layout

В контроллере:

```php
// Выбор layout в зависимости от условий
if ($isAdmin) {
    $blueprint->addGlobal('layout', 'layouts/admin');
} else {
    $blueprint->addGlobal('layout', 'layouts/main');
}
```

В шаблоне:

```blade
{% layout layout %}
```

---

## Вложенные sections

```blade
{% section content %}
    <div class="wrapper">
        <h1>{{ title }}</h1>
        
        {% section actions %}
            <a href="/edit" class="btn">Редактировать</a>
        {% endsection %}
        
        <div class="body">
            {{ content }}
        </div>
    </div>
{% endsection %}
```

---

## show

Вывод секции без объявления:

```blade
{# В layout #}
{% yield sidebar %}

{# Если секция не определена, можно показать дефолт #}
{% if not hasSection('sidebar') %}
    <div class="default-sidebar">
        Дефолтный сайдбар
    </div>
{% endif %}
```

---

## Лучшие практики

1. **Разделение** — layouts в отдельной папке
2. **Именование** — понятные имена секций
3. **Дефолты** — значения по умолчанию в layout
4. **DRY** — общие части в partials
5. **Гибкость** — несколько layout-ов для разных разделов
