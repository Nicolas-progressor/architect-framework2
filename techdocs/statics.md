# Статические хелперы (Statics)

Statics (ранее Units) – это набор статических классов-помощников, предоставляющих удобный API для часто используемых операций: управление заголовком страницы, навигационные цепочки, генерация HTML, работа с ресурсами и параметрами запроса. Хелперы доступны глобально через фасад `Statics`.

## Обзор хелперов

- **Title** – управление заголовком страницы (title).
- **Breadcrumbs** – построение навигационной цепочки (хлебных крошек).
- **Html** – генерация HTML-элементов (ссылки, изображения, формы).
- **Assets** – управление ресурсами (CSS, JavaScript, изображения).
- **Query** – работа с параметрами запроса (GET, POST).

## Инициализация

Statics автоматически инициализируется при запуске приложения через `Statics::init($container)`. Контейнер передаётся каждому хелперу, что позволяет им использовать другие сервисы (например, `router` для генерации URL).

## Title

Управление заголовком страницы (тег `<title>`).

### Методы

- `set(string $title): self` – установить заголовок.
- `append(string $suffix): self` – добавить суффикс.
- `prepend(string $prefix): self` – добавить префикс.
- `get(): string` – получить полный заголовок.
- `clear(): void` – очистить заголовок.

### Примеры

```php
Statics::Title()->set('Главная')->append(' | Мой сайт');
echo Statics::Title()->get(); // "Главная | Мой сайт"
```

В шаблоне:

```php
<title><?= Statics::Title()->get() ?></title>
```

## Breadcrumbs

Построение навигационной цепочки.

### Методы

- `add(string $title, ?string $url = null): self` – добавить элемент.
- `prepend(string $title, ?string $url = null): self` – добавить элемент в начало.
- `all(): array` – получить все элементы.
- `clear(): void` – очистить цепочку.
- `render(string $template = 'default'): string` – отрендерить HTML.

### Примеры

```php
Statics::Breadcrumbs()
    ->add('Главная', '/')
    ->add('Каталог', '/catalog')
    ->add('Ноутбуки');

foreach (Statics::Breadcrumbs()->all() as $crumb) {
    echo $crumb['title']; // Главная, Каталог, Ноутбуки
}
```

Шаблон рендеринга можно настроить через конфигурацию.

## Html

Генерация HTML-элементов.

### Методы

- `link(string $url, string $text, array $attributes = []): string` – гиперссылка.
- `image(string $src, string $alt = '', array $attributes = []): string` – изображение.
- `ul(array $items, array $attributes = []): string` – ненумерованный список.
- `ol(array $items, array $attributes = []): string` – нумерованный список.
- `tag(string $tag, string $content = '', array $attributes = []): string` – произвольный тег.
- `entities(string $text): string` – экранирование HTML-сущностей.

### Примеры

```php
echo Statics::Html()->link('/about', 'О нас', ['class' => 'btn']);
// <a href="/about" class="btn">О нас</a>

echo Statics::Html()->image('/img/logo.png', 'Логотип');
// <img src="/img/logo.png" alt="Логотип">
```

## Assets

Управление ресурсами (CSS, JavaScript, изображения). Позволяет подключать файлы с учётом версий и минификации.

### Методы

- `css(string|array $files, array $options = []): string` – подключить CSS.
- `js(string|array $files, array $options = []): string` – подключить JavaScript.
- `img(string $file, array $options = []): string` – получить путь к изображению.
- `version(string $file): string` – добавить версию на основе хеша файла.
- `addPath(string $path): void` – добавить путь к ресурсам.

### Примеры

```php
echo Statics::Assets()->css(['style.css', 'theme.css']);
// <link rel="stylesheet" href="/assets/style.css?v=abc123">
// <link rel="stylesheet" href="/assets/theme.css?v=def456">

echo Statics::Assets()->js('app.js', ['defer' => true]);
// <script src="/assets/app.js?v=ghi789" defer></script>
```

Конфигурация ресурсов находится в `app/config/assets.json`:

```json
{
    "paths": {
        "css": "htdocs/assets/css",
        "js": "htdocs/assets/js",
        "img": "htdocs/assets/images"
    },
    "versioning": true,
    "minify": false
}
```

## Query

Работа с параметрами запроса (GET, POST). Предоставляет безопасный доступ к данным запроса с фильтрацией.

### Методы

- `get(string $key, mixed $default = null): mixed` – получить GET-параметр.
- `post(string $key, mixed $default = null): mixed` – получить POST-параметр.
- `input(string $key, mixed $default = null): mixed` – получить параметр из GET или POST.
- `has(string $key): bool` – проверить наличие параметра.
- `all(): array` – все параметры.
- `only(array $keys): array` – только указанные ключи.
- `except(array $keys): array` – все кроме указанных ключей.

### Примеры

```php
$page = Statics::Query()->get('page', 1);
$search = Statics::Query()->input('q', '');

if (Statics::Query()->has('sort')) {
    $sort = Statics::Query()->get('sort');
}
```

## Расширение Statics

### Добавление собственного хелпера

1. Создайте класс в пространстве имён `Architect\Statics\YourHelper\YourHelper`:

```php
namespace Architect\Statics\YourHelper;

use Architect\Core\Contracts\ContainerInterface;

class YourHelper
{
    public function __construct(
        private ContainerInterface $container
    ) {}

    public function doSomething(): string
    {
        return 'Hello';
    }
}
```

2. Statics автоматически обнаружит хелпер по имени класса. Вызовите его:

```php
Statics::YourHelper()->doSomething();
```

### Переопределение существующего хелпера

Создайте класс с тем же именем в своём пространстве имён и зарегистрируйте его в контейнере с соответствующим ключом (например, `statics.YourHelper`).

## Интеграция с шаблонизатором

### В PHP-шаблонах

Используйте прямое обращение к `Statics::`.

```php
<?= Statics::Html()->link('/contact', 'Контакты') ?>
```

### В Blueprint

Blueprint предоставляет функции-обёртки:

```blade
{{ title('Заголовок') }}
{{ breadcrumbs() }}
{{ css('style.css') }}
```

Эти функции вызывают соответствующие методы Statics.

## Конфигурация

Настройки Statics находятся в `app/config/statics.json`:

```json
{
    "title": {
        "separator": " | ",
        "default": "Сайт"
    },
    "breadcrumbs": {
        "template": "default",
        "home_text": "Главная",
        "home_url": "/"
    },
    "assets": {
        "auto_version": true,
        "minify": false
    }
}
```

## Примеры

### Полный пример страницы

```php
// В контроллере
Statics::Title()->set('Профиль пользователя');
Statics::Breadcrumbs()
    ->add('Главная', '/')
    ->add('Пользователи', '/users')
    ->add('Профиль');

// В шаблоне
<!DOCTYPE html>
<html>
<head>
    <title><?= Statics::Title()->get() ?></title>
    <?= Statics::Assets()->css('profile.css') ?>
</head>
<body>
    <nav><?= Statics::Breadcrumbs()->render() ?></nav>
    <h1>Профиль</h1>
    <?= Statics::Html()->link('/logout', 'Выйти', ['class' => 'btn']) ?>
    <?= Statics::Assets()->js('profile.js') ?>
</body>
</html>
```

## Заключение

Statics предоставляет удобный и согласованный API для решения распространённых задач веб-разработки. Использование хелперов улучшает читаемость кода и снижает количество boilerplate.

Дополнительные сведения см. в [документации по интеграции](../docs2/integration.md#статические-хелперы-statics).