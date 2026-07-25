# Синтаксис шаблонов

Blueprint использует синтаксис, похожий на Blade и Twig.

---

## Комментарии

```blade
{# Это комментарий #}
{# Комментарии 
    могут быть 
    многострочными #}
```

Комментарии не попадают в скомпилированный вывод.

---

## Переменные

### Вывод с экранированием

```blade
{{ variable }}
{{ user.name }}
{{ user.email }}
```

По умолчанию вывод экранируется через `htmlspecialchars`.

### Вывод без экранирования

```blade
{!! html_content !!}
{!! raw_html !!}
```

Используйте `{!! !!}` для вывода HTML без экранирования.

### Доступ к свойствам

```blade
{{ user.name }}           {# Свойство объекта #}
{{ user.address.city }}   {# Вложенное свойство #}
{{ user.getName() }}      {# Метод объекта #}
{{ items.0 }}             {# Элемент массива по индексу #}
{{ data.key }}            {# Ключ массива #}
```

### Цепочки вызовов

```blade
{{ user.getProfile().getAvatar() }}
{{ request.getParams().get('id') }}
```

---

## Фильтры

Фильтры применяются через `|`:

```blade
{{ name | upper }}                    {# Верхний регистр #}
{{ text | truncate(100) }}            {# Обрезка текста #}
{{ price | number_format(2) }}        {# Форматирование числа #}
```

### Цепочки фильтров

```blade
{{ name | trim | upper | truncate(10) }}
```

### Фильтры с аргументами

```blade
{{ text | truncate(50, '...') }}
{{ price | number_format(2, '.', ' ') }}
{{ date | date('d.m.Y H:i') }}
```

[Полный список фильтров](filters.md)

---

## Управляющие конструкции

Все управляющие конструкции используют `{% %}`.

### Условия

```blade
{% if user.isLoggedIn() %}
    Добро пожаловать, {{ user.name }}!
{% elseif user.isGuest() %}
    Пожалуйста, войдите
{% else %}
    Ошибка
{% endif %}
```

#### Операторы сравнения

```blade
{% if count > 0 %}
{% if count >= 10 %}
{% if count < 5 %}
{% if count <= 5 %}
{% if status == 'active' %}
{% if status != 'inactive' %}
{% if a === b %}
{% if a !== b %}
```

#### Логические операторы

```blade
{% if a and b %}       {# И #}
{% if a or b %}        {# ИЛИ #}
{% if not a %}         {# НЕ #}
```

#### Оператор `in`

```blade
{% if status in ['active', 'pending'] %}
{% if id not in [1, 2, 3] %}
```

#### Проверка на пустоту

```blade
{% if items is empty %}
    Нет элементов
{% endif %}

{% if items is not empty %}
    Есть элементы
{% endif %}
```

---

### Циклы

#### for

```blade
{% for item in items %}
    <li>{{ item.name }}</li>
{% endfor %}
```

#### for с ключом

```blade
{% for key, item in items %}
    <li>{{ key }}: {{ item }}</li>
{% endfor %}
```

#### Переменная loop

Внутри цикла доступна переменная `loop`:

| Свойство | Описание |
|----------|----------|
| `loop.index` | Итерация (начиная с 1) |
| `loop.index0` | Итерация (начиная с 0) |
| `loop.first` | Первая итерация? |
| `loop.last` | Последняя итерация? |
| `loop.length` | Общее количество элементов |

```blade
{% for item in items %}
    <li class="{% if loop.first %}first{% endif %}{% if loop.last %}last{% endif %}">
        {{ loop.index }}. {{ item.name }}
    </li>
{% endfor %}
```

#### foreach

Альтернативный синтаксис:

```blade
{% foreach item in items %}
    {{ item }}
{% endforeach %}

{% foreach users as user %}
    {{ user.name }}
{% endforeach %}
```

---

### Тernary оператор

```blade
{{ status ? 'Активен' : 'Неактивен' }}
{{ count > 0 ? count : 'Нет' }}
{{ user ? user.name : 'Гость' }}
```

---

## Присваивание переменных

```blade
{% set name = 'John' %}
{% set count = items | length %}
{% set isActive = true %}
```

---

## Массивы

### Литерал массива

```blade
{% set colors = ['red', 'green', 'blue'] %}
{% set user = {name: 'John', age: 30} %}
```

### Доступ к элементам

```blade
{{ colors.0 }}
{{ user.name }}
{{ items[key] }}
```

---

## Операторы

| Оператор | Описание | Пример |
|----------|----------|--------|
| `+` | Сложение | `{{ a + b }}` |
| `-` | Вычитание | `{{ a - b }}` |
| `*` | Умножение | `{{ a * b }}` |
| `/` | Деление | `{{ a / b }}` |
| `%` | Остаток от деления | `{{ a % b }}` |
| `~` | Конкатенация строк | `{{ 'Hello ' ~ name }}` |
| `..` | Диапазон | `{% for i in 1..10 %}` |

---

## Экранирование

Если нужно вывести `{{` или `{%` как текст:

```blade
{{ '{{' }}
{{ '{%' }}
```

Или используйте `{% raw %}`:

```blade
{% raw %}
    Пример кода: {{ variable }}
{% endraw %}
```

---

## Raw-фильтр

Для вывода без экранирования:

```blade
{{ html | raw }}
{!! html !!}
```
