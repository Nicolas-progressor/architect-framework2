# Встроенные фильтры

Blueprint включает более 40 фильтров для обработки данных.

---

## Строковые фильтры

### upper

Преобразует строку в верхний регистр.

```blade
{{ name | upper }}
{# 'hello' → 'HELLO' #}
```

### lower

Преобразует строку в нижний регистр.

```blade
{{ name | lower }}
{# 'HELLO' → 'hello' #}
```

### capitalize

Первую букву делает заглавной, остальные — строчными.

```blade
{{ name | capitalize }}
{# 'hELLO' → 'Hello' #}
```

### title

Каждое слово с заглавной буквы.

```blade
{{ title | title }}
{# 'hello world' → 'Hello World' #}
```

### trim

Удаляет пробелы в начале и конце строки.

```blade
{{ name | trim }}
{# '  hello  ' → 'hello' #}
```

### truncate(length, suffix)

Обрезает строку до указанной длины (включая суффикс).

```blade
{{ text | truncate(10) }}
{# 'Hello World' → 'Hello W...' #}

{{ text | truncate(10, '…') }}
{# 'Hello World' → 'Hello W…' #}
```

### replace(search, replace)

Заменяет все вхождения.

```blade
{{ text | replace('world', 'universe') }}
{# 'Hello world' → 'Hello universe' #}
```

### striptags

Удаляет HTML-теги.

```blade
{{ html | striptags }}
{# '<p>Hello</p>' → 'Hello' #}
```

### escape, e

HTML-экранирование (применяется автоматически).

```blade
{{ html | escape }}
{{ html | e }}
{# '<script>' → '&lt;script&gt;' #}
```

### raw

Отключает экранирование.

```blade
{{ html | raw }}
{# '<b>bold</b>' → '<b>bold</b>' #}
```

### nl2br

Преобразует переносы строк в `<br>`.

```blade
{{ text | nl2br }}
{# "Hello\nWorld" → "Hello<br>\nWorld" #}
```

### slice(offset, length)

Вырезает часть строки.

```blade
{{ text | slice(0, 5) }}
{# 'Hello World' → 'Hello' #}
```

### split(delimiter)

Разбивает строку на массив.

```blade
{% set parts = text | split(',') %}
```

### pad(length, string, type)

Дополняет строку до указанной длины.

```blade
{{ num | pad(5, '0') }}
{# '42' → '00042' #}
```

### reverse

Переворачивает строку.

```blade
{{ name | reverse }}
{# 'hello' → 'olleh' #}
```

### length

Длина строки.

```blade
{{ name | length }}
{# 'hello' → 5 #}
```

---

## Числовые фильтры

### abs

Модуль числа.

```blade
{{ num | abs }}
{# -5 → 5 #}
```

### round(precision)

Округление.

```blade
{{ price | round(2) }}
{# 99.999 → 100.00 #}
```

### floor

Округление вниз.

```blade
{{ num | floor }}
{# 4.9 → 4 #}
```

### ceil

Округление вверх.

```blade
{{ num | ceil }}
{# 4.1 → 5 #}
```

### number_format(decimals, point, separator)

Форматирование числа.

```blade
{{ price | number_format(2, '.', ' ') }}
{# 1234.5 → '1 234.50' #}
```

---

## Фильтры массивов

### length

Количество элементов.

```blade
{{ items | length }}
```

### first

Первый элемент.

```blade
{{ items | first }}
```

### last

Последний элемент.

```blade
{{ items | last }}
```

### join(separator)

Объединяет элементы в строку.

```blade
{{ items | join(', ') }}
{# ['a', 'b', 'c'] → 'a, b, c' #}
```

### sort

Сортирует массив.

```blade
{% for item in items | sort %}
    {{ item }}
{% endfor %}
```

### reverse

Переворачивает массив.

```blade
{% for item in items | reverse %}
    {{ item }}
{% endfor %}
```

### slice(offset, length)

Срез массива.

```blade
{% for item in items | slice(0, 5) %}
    {{ item }}
{% endfor %}
```

### keys

Ключи массива.

```blade
{% for key in items | keys %}
    {{ key }}
{% endfor %}
```

### merge

Объединяет массивы.

```blade
{% set all = items1 | merge(items2) %}
```

---

## Фильтры дат

### date(format)

Форматирование даты.

```blade
{{ timestamp | date('d.m.Y') }}
{{ timestamp | date('H:i:s') }}
{{ 'now' | date('Y-m-d') }}
```

### date_modify(modifier)

Изменение даты.

```blade
{{ date | date_modify('+1 day') | date('d.m.Y') }}
```

---

## Прочие фильтры

### default(value)

Значение по умолчанию для пустых значений.

```blade
{{ name | default('Гость') }}
{{ price | default(0) }}
```

### json

Преобразует в JSON.

```blade
{{ data | json }}
```

### keys

Ключи массива или свойства объекта.

```blade
{% for key in user | keys %}
    {{ key }}
{% endfor %}
```

### type

Тип переменной.

```blade
{{ value | type }}
{# 'string', 'array', 'object', etc. #}
```

---

## Цепочки фильтров

Фильтры можно объединять:

```blade
{{ name | trim | upper | truncate(10) }}
{{ items | sort | reverse | first }}
{{ price | round(2) | number_format(2) }}
```
