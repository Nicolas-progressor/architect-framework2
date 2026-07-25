# Blueprint Examples

Примеры использования Blueprint Template Engine.

## Структура

```
examples/
├── Elements/              # PHP классы элементов
│   ├── AlertElement.php   # Пример с шаблоном
│   ├── BadgeElement.php   # Пример чистого PHP
│   └── CardElement.php    # Пример с обоими режимами
│
└── elements/              # Шаблоны элементов (.blu)
    ├── alert.blu
    ├── badge.blu
    └── card.blu
```

## Элементы

### AlertElement — PHP класс + шаблон

Показывает как создать элемент с отдельным .blu шаблоном.
Класс готовит данные, шаблон отображает.

```php
// Регистрация
$blueprint->registerElementClass('alert', \App\Elements\AlertElement::class);

// Использование
{!! element('alert', ['type' => 'success', 'message' => 'Saved!']) !!}
```

### BadgeElement — Только PHP

Показывает как создать простой элемент без шаблона.
Весь HTML генерируется в классе.

```php
// Регистрация
$blueprint->registerElementClass('badge', \App\Elements\BadgeElement::class);

// Использование
{{ element('badge', ['text' => 'New', 'type' => 'success']) }}
```

### CardElement — Оба режима

Показывает как переключаться между PHP и шаблоном.
Раскомментируйте `$template` для использования шаблона.

## Шаблоны .blu

Файлы в `elements/` показывают примеры шаблонов для элементов.
Могут использоваться отдельно от PHP классов.

```blade
{# Простой элемент без класса #}
{% element 'copyright' with {year: 2024} %}
```

## Документация

- [Элементы](../docs/elements.md) — полное описание системы элементов
- [Синтаксис](../docs/syntax.md) — синтаксис шаблонов
- [API](../docs/api.md) — API Reference
