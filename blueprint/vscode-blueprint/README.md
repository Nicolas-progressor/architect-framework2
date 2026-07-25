# Blueprint Template Engine - VS Code Extension

[![VS Code](https://img.shields.io/badge/VS%20Code-Extension-blue)](https://code.visualstudio.com/)
[![Blueprint](https://img.shields.io/badge/Blueprint-Template%20Engine-orange)](https://github.com/architect/blueprint)

Синтаксическая подсветка и сниппеты для **Blueprint Template Engine** (`.blu` файлы).

## Возможности

- ✅ Подсветка синтаксиса для `.blu` файлов
- ✅ Автозакрытие тегов `{% %}`, `{{ }}`, `{!! !!}`, `{# #}`
- ✅ Интеллектуальные сниппеты
- ✅ Подсветка фильтров, функций, операторов
- ✅ Встраивание HTML, CSS, JavaScript, PHP

## Установка

### Из Marketplace

1. Откройте Extensions в VS Code (`Ctrl+Shift+X`)
2. Найдите "Blueprint Template Engine"
3. Нажмите Install

### Из файла VSIX

```bash
code --install-extension vscode-blueprint-1.0.0.vsix
```

### Из исходников

```bash
cd blueprint/vscode-blueprint
npm install
npm run compile
# Нажмите F5 в VS Code для запуска в режиме разработки
```

## Синтаксис

Blueprint использует Blade/Twig-подобный синтаксис:

```blade
{# Комментарий #}

{{ variable }}           {# Переменная с экранированием #}
{!! variable !!}         {# Переменная без экранирования #}

{% if condition %}       {# Условие #}
    ...
{% elseif other %}
    ...
{% else %}
    ...
{% endif %}

{% for item in items %}  {# Цикл #}
    {{ loop.index }}. {{ item.name }}
{% endfor %}

{% extends 'base' %}     {# Наследование #}
{% block content %}...{% endblock %}

{% include 'partial' %}  {# Включение #}
{% element 'widget' %}   {# Элемент #}
```

## Сниппеты

| Префикс | Описание |
|---------|----------|
| `if` | If statement |
| `ifelse` | If-else statement |
| `ifelseif` | If-elseif-else statement |
| `for` | For loop |
| `forkey` | For loop with key |
| `block` | Block definition |
| `extends` | Template extends |
| `include` | Include template |
| `includewith` | Include with data |
| `element` | Render element |
| `elementwith` | Element with data |
| `var` | Output variable |
| `varraw` | Output variable (raw) |
| `comment` | Comment |
| `filter` | Variable with filter |
| `set` | Set variable |
| `html5` | HTML5 template |
| `bscard` | Bootstrap card |
| `navbar` | Bootstrap navbar |

## Примеры

### Базовый шаблон

```blade
{% extends 'layout' %}

{% block title %}Главная страница{% endblock %}

{% block content %}
    <h1>{{ title }}</h1>
    
    {% if posts is not empty %}
        {% for post in posts %}
            <article>
                <h2>{{ post.title }}</h2>
                <p>{{ post.excerpt|truncate(200) }}</p>
            </article>
        {% endfor %}
    {% else %}
        <p>Нет записей</p>
    {% endif %}
    
    {% element 'pagination' with {page: currentPage, total: totalPages} %}
{% endblock %}
```

### Наследование шаблонов

**layout.blu:**
```blade
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}Сайт{% endblock %}</title>
</head>
<body>
    {% element 'navbar' %}
    <main>
        {% block content %}{% endblock %}
    </main>
    {% element 'footer' %}
</body>
</html>
```

**page.blu:**
```blade
{% extends 'layout' %}

{% block title %}Страница{% endblock %}

{% block content %}
    <h1>Контент страницы</h1>
{% endblock %}
```

## Фильтры

Blueprint поддерживает более 40 встроенных фильтров:

```blade
{{ name|upper }}
{{ text|truncate(100) }}
{{ price|number_format(2, '.', ' ') }}
{{ date|date('d.m.Y') }}
{{ content|raw }}
{{ items|join(', ') }}
{{ name|default('Гость') }}
```

## Настройки

Добавьте в `settings.json`:

```json
{
  "files.associations": {
    "*.blu": "blueprint"
  },
  "emmet.includeLanguages": {
    "blueprint": "html"
  }
}
```

## Связи

- [Blueprint Documentation](../docs/)
- [Architect Framework](https://github.com/architect/framework)

## Лицензия

MIT License
