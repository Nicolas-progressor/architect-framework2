# Элементы и виджеты

Blueprint поддерживает повторно используемые компоненты — элементы и виджеты.

---

## Основные концепции

| Тег | Описание |
|-----|----------|
| `{% element 'name' %}` | Подключает элемент |
| `{% widget 'name' %}` | Алиас для element |
| `element('name')` | Функция для вывода |

---

## Создание элемента

### Файл элемента

**elements/navbar.blade.php:**

```blade
<nav class="navbar {{ class | default('') }}">
    <div class="navbar-brand">
        <a href="/">{{ siteName | default('Сайт') }}</a>
    </div>
    
    <ul class="navbar-menu">
        {% for item in items | default([]) %}
            <li class="{% if item.active | default(false) %}active{% endif %}">
                <a href="{{ item.url }}">{{ item.title }}</a>
            </li>
        {% endfor %}
    </ul>
    
    {% if user | default(false) %}
        <div class="navbar-user">
            {{ user.name }}
        </div>
    {% endif %}
</nav>
```

---

## Использование элементов

### Базовое подключение

```blade
{% element 'navbar' %}
```

### С передачей данных

```blade
{% element 'navbar' with {
    siteName: 'Мой сайт',
    items: [
        {title: 'Главная', url: '/', active: true},
        {title: 'О нас', url: '/about', active: false},
        {title: 'Контакты', url: '/contact', active: false},
    ]
} %}
```

### Как виджет

```blade
{% widget 'latest_posts' with {count: 5} %}
```

### Через функцию

```blade
{!! element('navbar', {class: 'navbar-dark'}) !!}
```

---

## Структура элементов

```
templates/
├── elements/
│   ├── navbar.blade.php
│   ├── sidebar.blade.php
│   ├── footer.blade.php
│   └── widgets/
│       ├── latest_posts.blade.php
│       ├── popular_tags.blade.php
│       └── user_card.blade.php
└── pages/
    └── home.blade.php
```

---

## Примеры элементов

### Карточка пользователя

**elements/user_card.blade.php:**

```blade
<div class="user-card">
    <img src="{{ user.avatar | default('/img/default-avatar.png') }}" 
         alt="{{ user.name }}" 
         class="user-card__avatar">
    
    <div class="user-card__info">
        <h3 class="user-card__name">{{ user.name }}</h3>
        <p class="user-card__email">{{ user.email }}</p>
        
        {% if showStats | default(false) %}
            <div class="user-card__stats">
                <span>Постов: {{ user.posts_count | default(0) }}</span>
                <span>Комментариев: {{ user.comments_count | default(0) }}</span>
            </div>
        {% endif %}
    </div>
    
    {% if linkToProfile | default(true) %}
        <a href="/users/{{ user.id }}" class="user-card__link">
            Профиль
        </a>
    {% endif %}
</div>
```

Использование:

```blade
{% element 'user_card' with {
    user: currentUser,
    showStats: true,
    linkToProfile: true
} %}
```

### Виджет последних постов

**elements/widgets/latest_posts.blade.php:**

```blade
<div class="widget widget-latest-posts">
    <h3 class="widget__title">{{ title | default('Последние посты') }}</h3>
    
    {% if posts | length > 0 %}
        <ul class="widget__list">
            {% for post in posts %}
                <li class="widget__item">
                    <a href="/posts/{{ post.slug }}">
                        {{ post.title }}
                    </a>
                    <time>{{ post.created_at | date('d.m.Y') }}</time>
                </li>
            {% endfor %}
        </ul>
        
        {% if showMore | default(false) %}
            <a href="/posts" class="widget__more">
                Все посты
            </a>
        {% endif %}
    {% else %}
        <p class="widget__empty">Нет постов</p>
    {% endif %}
</div>
```

### Форма поиска

**elements/search_form.blade.php:**

```blade
<form action="{{ action | default('/search') }}" 
      method="{{ method | default('GET') }}" 
      class="search-form">
    
    <input type="text" 
           name="q" 
           value="{{ query | default('') }}"
           placeholder="{{ placeholder | default('Поиск...') }}"
           class="search-form__input">
    
    <button type="submit" class="search-form__button">
        {% if iconOnly | default(false) %}
            🔍
        {% else %}
            Найти
        {% endif %}
    </button>
</form>
```

---

## Элементы с состоянием

### Алерт

**elements/alert.blade.php:**

```blade
<div class="alert alert-{{ type | default('info') }} {% if dismissible | default(false) %}alert-dismissible{% endif %}"
     role="alert">
    
    {% if icon | default(true) %}
        <span class="alert__icon">
            {% if type == 'success' %}✓{% endif %}
            {% if type == 'warning' %}⚠{% endif %}
            {% if type == 'error' %}✗{% endif %}
            {% if type == 'info' %}ℹ{% endif %}
        </span>
    {% endif %}
    
    <div class="alert__content">
        {% if title %}
            <strong class="alert__title">{{ title }}</strong>
        {% endif %}
        
        <p class="alert__message">{{ message }}</p>
    </div>
    
    {% if dismissible %}
        <button type="button" class="alert__close" data-dismiss="alert">×</button>
    {% endif %}
</div>
```

Использование:

```blade
{% element 'alert' with {
    type: 'success',
    title: 'Успешно!',
    message: 'Данные сохранены.',
    dismissible: true
} %}
```

---

## Вложенные элементы

```blade
{# elements/card.blade.php #}
<div class="card">
    <div class="card__header">
        {% element 'card_header' with {title: title} %}
    </div>
    <div class="card__body">
        {% element 'card_body' with {content: content} %}
    </div>
    <div class="card__footer">
        {% element 'card_footer' with {actions: actions} %}
    </div>
</div>
```

---

## Регистрация элементов

Элементы автоматически ищутся в папках:

1. `templates/elements/`
2. `templates/widgets/`

Можно зарегистрировать дополнительные пути:

```php
$blueprint->addPath('app/components');
```

---

## Лучшие практики

1. **DRY** — выносите повторяющийся UI в элементы
2. **Параметры** — используйте дефолтные значения
3. **Именование** — понятные имена файлов
4. **Структура** — группируйте по папкам
5. **Документация** — комментируйте параметры

```
elements/
├── layout/
│   ├── header.blade.php
│   ├── footer.blade.php
│   └── sidebar.blade.php
├── forms/
│   ├── input.blade.php
│   ├── select.blade.php
│   └── button.blade.php
├── cards/
│   ├── user_card.blade.php
│   ├── post_card.blade.php
│   └── product_card.blade.php
└── widgets/
    ├── latest_posts.blade.php
    └── popular_tags.blade.php
```
