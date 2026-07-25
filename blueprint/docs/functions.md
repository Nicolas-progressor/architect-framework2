# Встроенные функции

Blueprint включает функции для работы с данными в шаблонах.

---

## Строковые функции

### strlen

Длина строки.

```blade
{{ strlen(name) }}
```

### substr(string, start, length)

Подстрока.

```blade
{{ substr(text, 0, 10) }}
```

### strpos(string, search)

Позиция подстроки.

```blade
{{ strpos(text, 'world') }}
```

### str_replace(search, replace, subject)

Замена в строке.

```blade
{{ str_replace('world', 'universe', text) }}
```

### strtolower, strtoupper

Изменение регистра.

```blade
{{ strtolower(name) }}
{{ strtoupper(name) }}
```

### trim

Удаление пробелов.

```blade
{{ trim(name) }}
```

---

## Массивные функции

### count

Количество элементов.

```blade
{{ count(items) }}
```

### array_keys

Ключи массива.

```blade
{% for key in array_keys(items) %}
    {{ key }}
{% endfor %}
```

### array_values

Значения массива.

```blade
{% for value in array_values(items) %}
    {{ value }}
{% endfor %}
```

### array_merge

Объединение массивов.

```blade
{% set all = array_merge(items1, items2) %}
```

### array_slice

Срез массива.

```blade
{% for item in array_slice(items, 0, 5) %}
    {{ item }}
{% endfor %}
```

### in_array

Проверка наличия элемента.

```blade
{% if in_array('active', statuses) %}
    ...
{% endif %}
```

---

## Математические функции

### min, max

Минимум и максимум.

```blade
{{ min(1, 2, 3) }}
{{ max(1, 2, 3) }}
{{ min(items) }}
{{ max(items) }}
```

### abs

Модуль числа.

```blade
{{ abs(-5) }}
```

### round, floor, ceil

Округление.

```blade
{{ round(3.14159, 2) }}
{{ floor(3.9) }}
{{ ceil(3.1) }}
```

### rand

Случайное число.

```blade
{{ rand(1, 100) }}
```

### sqrt

Квадратный корень.

```blade
{{ sqrt(16) }}
```

### pow

Возведение в степень.

```blade
{{ pow(2, 8) }}
```

---

## Функции дат

### date

Текущая дата или форматирование.

```blade
{{ date('d.m.Y') }}
{{ date('Y-m-d H:i:s') }}
```

### time

Текущий timestamp.

```blade
{{ time() }}
```

### strtotime

Преобразование строки в timestamp.

```blade
{{ strtotime('+1 day') }}
{{ strtotime('next Monday') }}
```

---

## URL-функции

### urlencode, urldecode

Кодирование/декодирование URL.

```blade
{{ urlencode(text) }}
{{ urldecode(encoded) }}
```

### rawurlencode, rawurldecode

RFC 3986 кодирование.

```blade
{{ rawurlencode(text) }}
```

### http_build_query

Построение query-строки.

```blade
{{ http_build_query(params) }}
```

---

## Функции отладки

### dump

Вывод переменной для отладки.

```blade
{{ dump(user) }}
{{ dump() }}  {# Все переменные #}
```

### var_dump

Вывод типа и значения.

```blade
{{ var_dump(data) }}
```

### print_r

Читаемый вывод массива/объекта.

```blade
{{ print_r(items) }}
```

---

## Прочие функции

### range

Создание диапазона чисел.

```blade
{% for i in range(1, 10) %}
    {{ i }}
{% endfor %}

{% for i in range(0, 100, 10) %}
    {{ i }}
{% endfor %}
```

### empty

Проверка на пустоту.

```blade
{% if empty(items) %}
    Нет элементов
{% endif %}
```

### isset

Проверка существования.

```blade
{% if isset(user.name) %}
    {{ user.name }}
{% endif %}
```

### is_numeric, is_string, is_array, is_object

Проверка типа.

```blade
{% if is_numeric(value) %}
    {{ value * 2 }}
{% endif %}
```

### json_encode, json_decode

Работа с JSON.

```blade
{{ json_encode(data) }}
{% set data = json_decode(json_string) %}
```

---

## Создание собственных функций

```php
$blueprint->registerFunction('asset', function($path) {
    return '/assets/' . $path;
});
```

Использование:

```blade
<link href="{{ asset('css/style.css') }}" rel="stylesheet">
<script src="{{ asset('js/app.js') }}"></script>
```

С несколькими аргументами:

```php
$blueprint->registerFunction('url', function($route, $params = []) {
    return '/' . $route . ($params ? '?' . http_build_query($params) : '');
});
```

```blade
<a href="{{ url('user/profile', {id: user.id}) }}">
    Профиль
</a>
```
