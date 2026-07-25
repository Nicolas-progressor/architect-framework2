# Наследование шаблонов

Blueprint поддерживает наследование шаблонов через блоки.

---

## Базовый шаблон

**base.blade.php:**

```blade
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>{% block title %}Сайт{% endblock %}</title>
    
    {% block meta %}
        <meta name="viewport" content="width=device-width, initial-scale=1">
    {% endblock %}
    
    {% block styles %}
        <link href="/css/app.css" rel="stylesheet">
    {% endblock %}
</head>
<body>
    <header>
        {% block header %}
            <nav>
                <a href="/">Главная</a>
                <a href="/about">О нас</a>
            </nav>
        {% endblock %}
    </header>
    
    <main>
        {% block content %}{% endblock %}
    </main>
    
    <footer>
        {% block footer %}
            <p>&copy; {{ year }} Мой сайт</p>
        {% endblock %}
    </footer>
    
    {% block scripts %}
        <script src="/js/app.js"></script>
    {% endblock %}
</body>
</html>
```

---

## Дочерний шаблон

**page.blade.php:**

```blade
{% extends "base.blade.php" %}

{% block title %}Страница - Сайт{% endblock %}

{% block meta %}
    {{ parent() }}
    <meta name="description" content="Описание страницы">
{% endblock %}

{% block styles %}
    {{ parent() }}
    <link href="/css/page.css" rel="stylesheet">
{% endblock %}

{% block content %}
    <h1>Заголовок страницы</h1>
    <p>Содержимое страницы...</p>
{% endblock %}
```

---

## Ключевые концепции

### extends

Указывает, какой шаблон расширять. Должен быть первым тегом в шаблоне.

```blade
{% extends "base.blade.php" %}
{% extends 'layouts/main' %}
```

### block

Определяет именованный блок, который можно переопределить.

```blade
{% block content %}
    Контент по умолчанию
{% endblock %}
```

### parent()

Вызывает содержимое родительского блока.

```blade
{% block title %}
    {{ parent() }} - Дополнение
{% endblock %}
```

---

## Многоуровневое наследование

### Базовый шаблон

**layouts/base.blade.php:**

```blade
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}Сайт{% endblock %}</title>
</head>
<body>
    {% block body %}{% endblock %}
</body>
</html>
```

### Промежуточный шаблон

**layouts/app.blade.php:**

```blade
{% extends "layouts/base.blade.php" %}

{% block body %}
    <div class="container">
        {% block content %}{% endblock %}
    </div>
{% endblock %}
```

### Конечный шаблон

**pages/home.blade.php:**

```blade
{% extends "layouts/app.blade.php" %}

{% block title %}Главная{% endblock %}

{% block content %}
    <h1>Добро пожаловать!</h1>
{% endblock %}
```

---

## Условные блоки

```blade
{% block sidebar %}
    {% if user.isLoggedIn %}
        {% include 'partials/user_menu' %}
    {% else %}
        {% include 'partials/login_form' %}
    {% endif %}
{% endblock %}
```

---

## Вложенные блоки

```blade
{% block content %}
    <article>
        {% block article_header %}
            <h1>{{ title }}</h1>
        {% endblock %}
        
        {% block article_body %}
            {{ content | raw }}
        {% endblock %}
    </article>
{% endblock %}
```

---

## include

Включение других шаблонов.

```blade
{% include 'partials/header' %}
{% include 'partials/menu' with {active: 'home'} %}
```

### Передача данных в include

```blade
{% include 'user_card' with {user: currentUser} %}
```

### Условное включение

```blade
{% include 'sidebar' ignore missing %}
```

---

## use

Импорт блоков из другого шаблона без наследования.

```blade
{% use 'blocks/sidebar' %}

{% block sidebar %}
    {{ parent() }}
    <div>Дополнительный контент</div>
{% endblock %}
```

---

## Лучшие практики

1. **Один базовый шаблон** — для консистентного UI
2. **Много блоков** — для гибкого переопределения
3. **parent()** — для расширения, а не замены
4. **Именование** — понятные имена блоков
5. **Структура** — логичная иерархия шаблонов

```
templates/
├── base.blade.php           # Базовый
├── layouts/
│   ├── app.blade.php        # С сайдбаром
│   ├── full.blade.php       # На всю ширину
│   └── admin.blade.php      # Для админки
├── partials/
│   ├── header.blade.php
│   ├── footer.blade.php
│   └── sidebar.blade.php
└── pages/
    ├── home.blade.php
    └── about.blade.php
```
