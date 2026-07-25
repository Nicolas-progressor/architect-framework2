# Представления

Представления отвечают за отображение данных, подготовленных контроллерами. В Architect RED 2 вы можете использовать два типа шаблонов:

1. **Обычные PHP-шаблоны** — простые файлы с расширением `.php`, в которых можно смешивать PHP-код и HTML.
2. **Шаблоны Blueprint** — мощный шаблонизатор с синтаксисом, похожим на Blade/Twig, поддерживающий наследование, блоки, фильтры и многое другое.

В этом разделе вы узнаете, как создавать и использовать оба типа представлений, а также как работать с макетами (layouts), частичными шаблонами (partials) и передавать данные.

## Расположение представлений

Представления находятся в папке `view/` внутри модуля. Путь зависит от типа модуля (прикладной или глобальный):

- **Прикладной модуль:** `app/apps/{app}/modules/{module}/view/`
- **Глобальный модуль:** `app/modules/{module}/view/`

Имена файлов представлений могут быть любыми, но рекомендуется использовать строчные буквы и разделители через точку или подчёркивание (например, `index.php`, `user_profile.php`).

## PHP-шаблоны

PHP-шаблоны — это самый простой способ создать представление. Вы можете использовать любой PHP-код внутри файла, но для лучшей читаемости рекомендуется минимизировать логику и сосредоточиться на выводе данных.

### Создание PHP-шаблона

Создайте файл `app/apps/home/modules/users/view/index.php`:

```php
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1><?= htmlspecialchars($title) ?></h1>
        
        <?php if (!empty($users)): ?>
            <ul class="list-group">
                <?php foreach ($users as $user): ?>
                    <li class="list-group-item">
                        <?= htmlspecialchars($user['name']) ?> 
                        (<?= htmlspecialchars($user['email']) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Пользователей нет.</p>
        <?php endif; ?>
    </div>
</body>
</html>
```

### Передача данных в PHP-шаблон

Контроллер передаёт данные через массив `$this->ext` или напрямую в метод `display()`:

```php
class users extends Controller
{
    public function index_app_data(): void
    {
        $this->ext['title'] = 'Список пользователей';
        $this->ext['users'] = [
            ['name' => 'Иван', 'email' => 'ivan@example.com'],
            ['name' => 'Мария', 'email' => 'maria@example.com'],
        ];
    }

    public function index_app_output(): void
    {
        $this->display('index');
    }
}
```

В шаблоне переменные `$title` и `$users` будут доступны автоматически, потому что они извлечены через `extract()`.

### Безопасность вывода

Всегда экранируйте вывод пользовательских данных с помощью `htmlspecialchars()` или используйте фильтры Blueprint для автоматического экранирования.

## Шаблонизатор Blueprint

Blueprint — это современный шаблонизатор, вдохновлённый Blade (Laravel) и Twig. Он компилирует шаблоны в PHP для максимальной производительности и предоставляет множество удобных функций.

### Преимущества Blueprint

- **Наследование шаблонов** — создавайте базовые макеты и расширяйте их.
- **Блоки и секции** — определяйте переопределяемые области.
- **Более 40 встроенных фильтров и функций** — для форматирования данных, работы с массивами, строками, датами и т.д.
- **Автоматическое экранирование HTML** — по умолчанию все переменные экранируются.
- **Поддержка MVC Elements и Widgets** — reusable компоненты.
- **Интеграция с отладочной панелью** — отображение информации о шаблонах.

### Создание Blueprint-шаблона

Файлы Blueprint имеют расширение `.blu`. Создайте `app/apps/home/modules/users/view/index.blu`:

```blade
{% layout 'layouts/main' %}

{% section title %}Список пользователей{% endsection %}

{% section content %}
    <h1>Список пользователей</h1>
    
    {% if users|length > 0 %}
        <ul class="list-group">
            {% for user in users %}
                <li class="list-group-item">
                    {{ user.name }} ({{ user.email }})
                </li>
            {% endfor %}
        </ul>
    {% else %}
        <p>Пользователей нет.</p>
    {% endif %}
{% endsection %}
```

### Макеты (Layouts)

Макеты определяют общую структуру страницы (HTML, head, body, header, footer). Они хранятся в папке `template/layouts/` приложения или в общих шаблонах.

Пример макета `app/apps/home/template/layouts/main.blu`:

```blade
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>{% yield title %}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="/">Мой сайт</a>
        </div>
    </nav>
    
    <div class="container mt-5">
        {% yield content %}
    </div>
    
    <footer class="mt-5 py-3 bg-light text-center">
        <div class="container">
            &copy; {{ "now"|date('Y') }} Моя компания
        </div>
    </footer>
</body>
</html>
```

В шаблоне используется директива `{% layout 'layouts/main' %}` для указания макета и `{% section ... %}` для определения содержимого секций, которые затем выводятся в макете через `{% yield ... %}`.

### Передача данных в Blueprint

Данные передаются так же, как и в PHP-шаблоны. Blueprint автоматически экранирует переменные при выводе через `{{ ... }}`. Если нужно вывести сырой HTML, используйте фильтр `raw`:

```blade
{{ html_content|raw }}
```

### Фильтры и функции

Blueprint предоставляет множество фильтров для преобразования данных. Примеры:

```blade
{{ user.name|upper }}                     <!-- В верхний регистр -->
{{ user.created_at|date('d.m.Y H:i') }}   <!-- Форматирование даты -->
{{ description|truncate(100) }}           <!-- Обрезать строку -->
{{ price|format_currency('USD') }}        <!-- Форматирование валюты -->
{{ array|length }}                        <!-- Длина массива -->
{{ string|replace('old', 'new') }}        <!-- Замена подстроки -->
```

Полный список фильтров и функций смотрите в [документации Blueprint](../blueprint/docs/filters.md).

### Наследование шаблонов

Вы можете создавать иерархию шаблонов. Например, у вас может быть базовый макет `base.blu`, от которого наследуются `admin.blu` и `front.blu`.

**base.blu:**
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

**admin.blu:**
```blade
{% extends 'base' %}

{% block title %}Админка | {{ parent() }}{% endblock %}

{% block body %}
    <div class="admin-panel">
        {% block admin_content %}{% endblock %}
    </div>
{% endblock %}
```

**page.blu:**
```blade
{% extends 'admin' %}

{% block admin_content %}
    <h1>Управление пользователями</h1>
    ...
{% endblock %}
```

### Частичные шаблоны (Partials)

Для повторного использования фрагментов шаблонов создавайте частичные шаблоны (partials). Обычно они хранятся в подпапке `partials/` или `elements/`.

**elements/alert.blu:**
```blade
<div class="alert alert-{{ type }}">
    {{ message }}
</div>
```

Включение в основной шаблон:

```blade
{% include 'elements/alert' with {type: 'success', message: 'Данные сохранены!'} %}
```

### MVC Elements и Widgets

Blueprint поддерживает специальные элементы, которые могут иметь собственный PHP-класс для рендеринга. Это позволяет создавать сложные компоненты с логикой.

Пример элемента `AlertElement`:

```php
// app/apps/home/modules/common/elements/AlertElement.php
namespace app\home\modules\common\elements;

use Blueprint\Engine\Element;

class AlertElement extends Element
{
    public function render(array $data): string
    {
        $type = $data['type'] ?? 'info';
        $message = $data['message'] ?? '';
        
        return "<div class='alert alert-{$type}'>{$message}</div>";
    }
}
```

В шаблоне:

```blade
{% element 'Alert' type='warning' message='Внимание!' %}
```

## Использование представлений в контроллерах

### Методы render() и display()

- `$this->render(string $template, array $data = [])` — возвращает отрендеренный контент как строку.
- `$this->display(string $template, array $data = [])` — выводит контент напрямую (используется в `app_output` этапе).

Пример:

```php
public function show_app_output(): void
{
    $this->display('show', [
        'post' => $this->ext['post'],
        'comments' => $this->ext['comments']
    ]);
}
```

### Установка макета (Layout)

Вы можете задать макет для всего действия через сервис `template`:

```php
$this->setTemplate('layouts/admin');
```

Или отключить макет:

```php
$this->noTemplate(); // вывод только контента представления
```

## Работа с активами (CSS, JS, изображения)

Статические файлы (CSS, JavaScript, изображения) обычно размещаются в `htdocs/assets/`. Для их подключения в шаблонах используйте абсолютные или относительные пути.

### Хелпер Assets

Фреймворк предоставляет хелпер `Assets` для удобного управления активами. Пример использования в Blueprint:

```blade
{{ assets.css('css/app.css') }}
{{ assets.js('js/app.js') }}
{{ assets.image('images/logo.png') }}
```

В PHP-шаблонах:

```php
<?= $this->get('assets')->css('css/app.css') ?>
```

## Отладка шаблонов

- **Отладочная панель** — вкладка "Views" показывает загруженные шаблоны, время рендеринга и переданные данные.
- **Компилированные шаблоны** — Blueprint компилирует шаблоны в PHP-код, который сохраняется в `cache/blueprints/`. При необходимости можно просмотреть сгенерированный код.
- **Логирование** — сервис `logger` записывает информацию о рендеринге.

## Пример полного представления

### Контроллер

```php
class BlogController extends Controller
{
    public function post_app_data(): void
    {
        $model = $this->getModel('Post');
        $this->ext['post'] = $model->findById((int) $this->param('id'));
        $this->ext['title'] = $this->ext['post']['title'] ?? 'Пост';
    }

    public function post_app_output(): void
    {
        $this->display('post');
    }
}
```

### Шаблон `post.blu`

```blade
{% layout 'layouts/blog' %}

{% section title %}{{ post.title }} | Блог{% endsection %}

{% section content %}
    <article class="post">
        <h1>{{ post.title }}</h1>
        <div class="meta">
            Опубликовано {{ post.created_at|date('d.m.Y') }}
            автором {{ post.author }}
        </div>
        
        <div class="content">
            {{ post.content|raw }}
        </div>
        
        <div class="tags">
            {% for tag in post.tags %}
                <span class="badge bg-secondary">{{ tag }}</span>
            {% endfor %}
        </div>
    </article>
    
    <hr>
    
    <h3>Комментарии ({{ comments|length }})</h3>
    
    {% if comments|length > 0 %}
        {% for comment in comments %}
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">{{ comment.author }}</h5>
                    <p class="card-text">{{ comment.text }}</p>
                </div>
            </div>
        {% endfor %}
    {% else %}
        <p>Комментариев пока нет.</p>
    {% endif %}
{% endsection %}
```

## Заключение

Представления в Architect RED 2 дают вам гибкость выбора между простыми PHP-шаблонами и мощным шаблонизатором Blueprint. Blueprint рекомендуется для сложных проектов, где важны наследование, компоненты и безопасность вывода. PHP-шаблоны идеальны для простых страниц или когда нужно полное управление кодом.

Для более глубокого изучения Blueprint обратитесь к [официальной документации Blueprint](../blueprint/docs/index.md) и разделу [Шаблонизатор](templates.md).