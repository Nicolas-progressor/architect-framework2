# Создание первого приложения

В этом руководстве мы создадим простое приложение "Заметки" с использованием Architect RED 2. Вы научитесь создавать модули, контроллеры, модели, представления и маршруты.

## Предварительные требования

- Установленный и настроенный Architect RED 2 (см. [Установка](installation.md)).
- Браузер и текстовый редактор.

## Шаг 1: Структура приложения

По умолчанию фреймворк использует приложение `home`. Мы будем работать внутри него.

Перейдите в папку `app/apps/home/`. Если её нет, создайте (но обычно она есть).

## Альтернатива: Создание через консольные команды

Вместо ручного создания файлов вы можете использовать встроенные консольные команды Architect RED 2. Это быстрее и гарантирует правильную структуру.

```bash
# Создать модуль notes в приложении home
php arc make:module notes --app=home

# Создать контроллер notes в модуле notes
php arc make:controller notes --module=notes

# Создать модель Note в модуле notes
php arc make:model Note --module=notes

# Создать маршрут для notes
php arc make:route notes --app=home --module=notes --controller=notes --action=index
```

Эти команды автоматически создадут все необходимые файлы и папки. После этого вы можете перейти к редактированию кода.

Далее в руководстве мы будем создавать файлы вручную, чтобы лучше понять структуру, но вы можете использовать команды для ускорения.
## Шаг 2: Создание модуля

Модуль — это группа связанных функциональностей. Создадим модуль `notes`.

Внутри `app/apps/home/modules/` создайте папку `notes/` и следующие подпапки:

```
notes/
├── controller/
├── model/
├── view/
└── lang/ (опционально)
```

## Шаг 3: Создание контроллера

Контроллер обрабатывает запросы пользователя. Создайте файл `app/apps/home/modules/notes/controller/notes.php`:

```php
<?php

declare(strict_types=1);

namespace app\home\modules\notes\controller;

use Architect\Services\Mvc\Controller;

class notes extends Controller
{
    public function index_app_data(): void
    {
        // Подготовка данных для представления
        $this->ext['title'] = 'Мои заметки';
        $this->ext['notes'] = [
            ['id' => 1, 'text' => 'Первая заметка'],
            ['id' => 2, 'text' => 'Вторая заметка'],
        ];
    }

    public function index_app_output(): void
    {
        // Рендеринг представления
        $this->display('index');
    }
}
```

## Шаг 4: Создание представления

Представление отвечает за отображение данных. Создайте файл `app/apps/home/modules/notes/view/index.php`:

```php
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1><?= $title ?></h1>
        
        <?php if (!empty($notes)): ?>
            <ul class="list-group">
                <?php foreach ($notes as $note): ?>
                    <li class="list-group-item"><?= htmlspecialchars($note['text']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Заметок пока нет.</p>
        <?php endif; ?>
    </div>
</body>
</html>
```

## Шаг 5: Настройка маршрута

Маршрут связывает URL с контроллером и действием. Создайте файл `app/apps/home/routes/notes.json`:

```json
{
    "default": "index",
    "routes": {
        "notes": {
            "module": "notes",
            "controller": "notes",
            "action": "index"
        }
    }
}
```

Теперь URL `/notes` будет направлять на наш контроллер.

## Шаг 6: Проверка

Запустите веб-сервер (если ещё не запущен) и откройте в браузере `http://localhost/notes`. Вы должны увидеть страницу с заголовком "Мои заметки" и списком из двух заметок.

Если возникает ошибка 404, проверьте:
- Правильность пути к файлу маршрута.
- Наличие файла контроллера и правильность namespace.
- Что модуль `notes` находится в правильной папке.

## Шаг 7: Добавление модели

Модель отвечает за работу с данными. Создадим простую модель для работы с заметками.

Создайте файл `app/apps/home/modules/notes/model/Note.php`:

```php
<?php

declare(strict_types=1);

namespace app\home\modules\notes\model;

use Architect\Services\Mvc\ModelBase;

class Note extends ModelBase
{
    public function getAll(): array
    {
        // В реальном приложении здесь будет запрос к БД
        return [
            ['id' => 1, 'text' => 'Первая заметка из модели'],
            ['id' => 2, 'text' => 'Вторая заметка из модели'],
            ['id' => 3, 'text' => 'Третья заметка из модели'],
        ];
    }
}
```

Обновите контроллер, чтобы использовать модель:

```php
public function index_app_data(): void
{
    $model = $this->get('model')->create('Note');
    $this->ext['title'] = 'Мои заметки';
    $this->ext['notes'] = $model->getAll();
}
```

Обновите страницу в браузере — теперь заметки должны браться из модели.

## Шаг 8: Использование Blueprint шаблонов

Blueprint — это более мощный шаблонизатор. Давайте переведём представление на Blueprint.

Создайте файл `app/apps/home/modules/notes/view/index.blu`:

```blade
{% layout 'layouts/main' %}

{% section title %}Мои заметки{% endsection %}

{% section content %}
    <h1>Мои заметки</h1>
    
    {% if notes|length > 0 %}
        <ul class="list-group">
            {% for note in notes %}
                <li class="list-group-item">{{ note.text }}</li>
            {% endfor %}
        </ul>
    {% else %}
        <p>Заметок пока нет.</p>
    {% endif %}
{% endsection %}
```

Также нужно создать layout `layouts/main.blu` в папке шаблонов приложения. Создайте `app/apps/home/template/layouts/main.blu`:

```blade
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>{% yield title %}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        {% yield content %}
    </div>
</body>
</html>
```

Обновите контроллер, чтобы использовать Blueprint:

```php
public function index_app_output(): void
{
    $this->display('index'); // автоматически найдёт index.blu
}
```

Теперь страница будет использовать Blueprint шаблон с наследованием layout.

## Шаг 9: Добавление формы

Добавим форму для создания новой заметки. Расширим контроллер:

```php
public function create_app_output(): void
{
    $this->display('create');
}

public function store_app_data(): void
{
    // Обработка POST-запроса
    $text = $this->param('text', '');
    if (!empty($text)) {
        // Сохраняем заметку (пока в массив)
        $this->ext['message'] = 'Заметка сохранена!';
    }
    $this->redirectTo('/notes');
}
```

Создайте представление `create.blu`:

```blade
{% layout 'layouts/main' %}

{% section title %}Новая заметка{% endsection %}

{% section content %}
    <h1>Новая заметка</h1>
    <form method="POST" action="/notes/store">
        <div class="mb-3">
            <label for="text" class="form-label">Текст заметки</label>
            <textarea class="form-control" id="text" name="text" rows="3"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Сохранить</button>
    </form>
{% endsection %}
```

Добавьте маршрут в `notes.json`:

```json
"notes/create": {
    "module": "notes",
    "controller": "notes",
    "action": "create"
},
"notes/store": {
    "module": "notes",
    "controller": "notes",
    "action": "store"
}
```

Теперь вы можете перейти по `/notes/create`, заполнить форму и отправить её.

## Шаг 10: Использование отладочной панели

Включите отладочную панель в `app/config/debug.json` (установите `"enabled": true`). При открытии страницы внизу появится панель, где можно увидеть выполненные SQL-запросы, логи, время выполнения и другую полезную информацию.

## Заключение

Вы создали простое приложение "Заметки" с использованием Architect RED 2. Вы научились:

- Создавать модули, контроллеры, модели и представления.
- Настраивать маршруты.
- Использовать шаблонизатор Blueprint.
- Работать с отладочной панелью.

Это основа для дальнейшего изучения фреймворка. Рекомендуем ознакомиться с разделами [Модели](models.md), [База данных](database.md) и [Аутентификация](auth.md) для создания более сложных приложений.