# Представления (View)

Компонент View отвечает за рендеринг HTML-страниц и других типов ответов. Architect RED 2 поддерживает два типа представлений: **PHP-шаблоны** и **шаблонизатор Blueprint**. Компонент обеспечивает разделение логики приложения и отображения, а также предоставляет удобные методы для работы с данными.

## Класс View

Основной класс – `Architect\Services\Mvc\View`. Он инкапсулирует логику рендеринга и взаимодействует с сервисом шаблонов (Template) и контейнером зависимостей.

### Основные методы

- `render(string $template, array $data = []): string` – рендерит указанный шаблон с переданными данными.
- `json(array $data, int $status = 200): Response` – возвращает JSON-ответ.
- `redirect(string $url, int $status = 302): Response` – перенаправляет на другой URL.
- `with(string $key, mixed $value): self` – добавляет данные, которые будут переданы в следующий рендер.
- `share(string $key, mixed $value): void` – добавляет глобальные данные, доступные во всех шаблонах.

## Использование в контроллерах

В контроллерах доступ к View осуществляется через метод `$this->view()` (унаследованный от `Controller`).

```php
public function index()
{
    return $this->view('home', ['title' => 'Главная']);
}
```

Также можно использовать методы `json` и `redirect`:

```php
public function api()
{
    return $this->json(['status' => 'ok']);
}

public function logout()
{
    return $this->redirect('/login');
}
```

## Шаблоны

### PHP-шаблоны

PHP-шаблоны – это обычные PHP-файлы с расширением `.php`. Они располагаются в папке `app/template/` (или в подпапках модулей). Данные передаются в виде ассоциативного массива и извлекаются через переменные.

Пример шаблона `home.php`:

```php
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($title) ?></title>
</head>
<body>
    <h1>Welcome, <?= $user['name'] ?></h1>
</body>
</html>
```

### Шаблонизатор Blueprint

Blueprint – современный шаблонизатор с синтаксисом, похожим на Blade/Twig. Для его использования необходимо установить пакет `architect/blueprint`.

Шаблоны Blueprint имеют расширение `.blu` и поддерживают наследование, блоки, фильтры и функции.

Пример:

```blade
{% extends "layouts/main.blu" %}

{% block content %}
    <h1>{{ title }}</h1>
    <ul>
        {% for user in users %}
            <li>{{ user.name | upper }}</li>
        {% endfor %}
    </ul>
{% endblock %}
```

В контроллере рендеринг Blueprint осуществляется через метод `$this->blueprint()`:

```php
public function index()
{
    return $this->blueprint('home', ['title' => 'Главная']);
}
```

## Пути к шаблонам

View ищет шаблоны в следующих директориях (в порядке приоритета):

1. Папка текущего модуля: `app/modules/<module>/view/`
2. Папка общего шаблона: `app/template/`
3. Папки, определённые в конфигурации шаблонизатора.

Можно добавить дополнительные пути через сервис Template.

## Передача данных в шаблоны

Данные передаются в виде ассоциативного массива вторым аргументом метода `render` или `view`. Внутри шаблона ключи массива становятся переменными.

```php
$this->view('profile', ['user' => $user, 'posts' => $posts]);
```

В шаблоне:

```php
echo $user['name'];
```

### Глобальные данные

Глобальные данные доступны во всех шаблонах без явной передачи. Установить их можно через метод `share`:

```php
$this->view->share('site_name', 'My Site');
```

Или через сервис Template:

```php
$template = $container->get('template');
$template->addGlobal('site_name', 'My Site');
```

## Расширения шаблонов

### Пользовательские функции и фильтры (Blueprint)

В Blueprint можно зарегистрировать собственные функции и фильтры через API шаблонизатора.

```php
$blueprint = $container->get('blueprint');
$blueprint->registerFilter('rot13', fn($value) => str_rot13($value));
$blueprint->registerFunction('asset', fn($path) => '/assets/' . $path);
```

### Пользовательские хелперы (PHP-шаблоны)

В PHP-шаблонах можно использовать любые PHP-функции, а также подключать дополнительные библиотеки через `include`.

## Кэширование шаблонов

Blueprint поддерживает кэширование скомпилированных шаблонов. Включите его в конфигурации `app/config/template.json`:

```json
{
    "cache": true,
    "cache_path": "cache/views"
}
```

PHP-шаблоны не кэшируются на уровне фреймворка, но можно использовать OPCache.

## Отладка

Debug Panel содержит вкладку **Views**, где отображаются информация о рендеринге: использованные шаблоны, переданные данные, время выполнения.

Также можно включить логирование рендеринга, установив уровень `debug` для канала `template`.

## Интеграция с другими компонентами

### Statics

В шаблонах можно использовать статические хелперы (Title, Breadcrumbs, Html, Assets, Query) через глобальные функции или обращение к `Statics::`.

Пример в PHP-шаблоне:

```php
<title><?= Statics::Title()->get() ?></title>
```

В Blueprint:

```blade
<title>{{ title() }}</title>
```

### Формы

Генерация HTML-форм осуществляется через хелпер `Form` или `Html`. В шаблонах можно использовать функции `form_open`, `form_close`, `input` и т.д.

## Примеры

### Рендеринг с layout

Часто используется layout-система Blueprint:

```blade
{% layout "layouts/main.blu" %}

{% section content %}
    <p>Контент страницы</p>
{% endsection %}
```

### Условный рендеринг в PHP-шаблоне

```php
<?php if ($user->isAdmin()): ?>
    <a href="/admin">Панель управления</a>
<?php endif; ?>
```

## Заключение

Компонент View предоставляет гибкие инструменты для рендеринга ответов, поддерживая как классические PHP-шаблоны, так и современный шаблонизатор Blueprint. Использование View способствует чистой архитектуре и упрощает тестирование.

Дополнительные сведения см. в [документации по представлениям](../docs2/views.md) и [Blueprint](../docs2/integration.md#blueprint-шаблонизатор).