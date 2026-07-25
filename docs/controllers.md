# Контроллеры

Контроллеры — это центральный компонент архитектуры MVC в Architect RED 2. Они отвечают за обработку HTTP-запросов, взаимодействие с моделями и возврат ответов (HTML, JSON, редиректы и т.д.). В этом разделе вы узнаете, как создавать, настраивать и использовать контроллеры.

## Обзор

Каждый контроллер представляет собой класс PHP, наследуемый от `Architect\Services\Mvc\Controller`. Контроллеры располагаются в папке `controller/` внутри модуля. Имя файла должно соответствовать имени контроллера (в нижнем регистре, с расширением `.php`), а имя класса — в CamelCase.

**Пример структуры:**
```
app/apps/home/modules/users/controller/
├── users.php          # Контроллер users
└── AdminController.php # Контроллер AdminController
```

## Создание контроллера

### Вручную

Создайте файл контроллера в соответствующей папке модуля. Например, для модуля `users` приложения `home`:

```php
<?php

declare(strict_types=1);

namespace app\home\modules\users\controller;

use Architect\Services\Mvc\Controller;

class users extends Controller
{
    public function index_app_data(): void
    {
        // Подготовка данных для представления
        $this->ext['title'] = 'Список пользователей';
        $this->ext['users'] = [
            ['id' => 1, 'name' => 'Иван'],
            ['id' => 2, 'name' => 'Мария'],
        ];
    }

    public function index_app_output(): void
    {
        // Рендеринг представления
        $this->display('index');
    }
}
```

### С помощью консольной команды

Используйте команду `make:controller` для автоматического создания контроллера:

```bash
php arc make:controller UserController --module=users --app=home
```

Опции команды:
- `--module` — имя модуля (обязательно).
- `--app` — имя приложения (по умолчанию `home`).
- `--resource` — создать ресурсный контроллер с CRUD-методами.
- `--api` — создать API-контроллер (возвращает JSON).
- `--force` — перезаписать существующий файл.

## Жизненный цикл контроллера

Контроллеры в Architect RED 2 работают в рамках **statement-based** жизненного цикла. Каждое действие (action) разделено на этапы (stages), которые выполняются последовательно.

### Этапы (Stages)

1. **app_data** — подготовка данных (работа с моделью, бизнес-логика).
2. **app_output** — формирование вывода (рендеринг представления, отправка JSON и т.д.).

Для действия `index` будут вызваны методы:
- `index_app_data()`
- `index_app_output()`

Если метод для этапа отсутствует, этап пропускается.

### Пример с двумя этапами

```php
class products extends Controller
{
    public function list_app_data(): void
    {
        // Загружаем модель Product
        $model = $this->getModel('Product');
        $this->ext['products'] = $model->getAll();
    }

    public function list_app_output(): void
    {
        // Передаём данные в представление
        $this->display('list');
    }
}
```

## Методы контроллера

Базовый класс `Controller` предоставляет множество полезных методов для упрощения разработки.

### Работа с представлениями

- `display(string $template, array $data = [])` — рендерит и выводит шаблон.
- `render(string $template, array $data = [])` — возвращает отрендеренный контент как строку.
- `setTemplate(string $name)` — устанавливает общий шаблон (layout) для действия.
- `noTemplate()` — отключает использование шаблона (вывод только контента).
- `useTemplate()` — включает шаблон.

### Работа с ответами

- `json(mixed $data, int $statusCode = 200, int $options = 0)` — отправляет JSON-ответ.
- `html(string $content)` — устанавливает HTML-контент ответа.
- `text(string $text)` — устанавливает текстовый ответ.
- `redirectTo(string $url, int $status = 302)` — выполняет перенаправление.
- `getResponse(): ResponseInterface` — возвращает объект ответа для тонкой настройки.

### Работа с данными запроса

- `param(string $name, string $default = '')` — получает параметр URL (query string или route parameter).
- `segment(int $index, string $default = '')` — получает сегмент URL по индексу (1‑based).
- `get(string $id)` — получает сервис из контейнера зависимостей.

### Работа с моделями

- `getModel(string $name): ?object` — загружает модель по имени (ищет в папке `model/` текущего модуля).

### Middleware

Контроллеры поддерживают middleware для защиты действий или добавления дополнительной логики.

- `addMiddleware(string $middleware, array $options = [])` — регистрирует middleware.
- `middlewareOnly(string $middleware, array $actions)` — регистрирует middleware только для указанных действий.
- `middlewareExcept(string $middleware, array $actions)` — регистрирует middleware для всех действий, кроме указанных.
- `clearMiddleware()` — очищает все middleware.
- `getMiddleware(): array` — возвращает конфигурацию middleware.

Пример использования middleware:

```php
class users extends Controller
{
    public function __construct(ContainerInterface $container, ?string $module = null, bool $isGlobal = false)
    {
        parent::__construct($container, $module, $isGlobal);

        // Добавляем middleware аутентификации для всех действий, кроме 'login'
        $this->middlewareExcept(AuthMiddleware::class, ['login']);
    }
}
```

## Ресурсные контроллеры

Ресурсный контроллер — это контроллер, который предоставляет стандартные CRUD-действия для работы с сущностью (например, пользователями). Architect RED 2 может автоматически генерировать ресурсный контроллер с помощью команды `make:controller --resource`.

Действия ресурсного контроллера:

| Метод       | URL           | Действие   | Описание                     |
|-------------|---------------|------------|------------------------------|
| index       | GET /users    | index      | Список всех записей          |
| create      | GET /users/create | create  | Форма создания новой записи  |
| store       | POST /users   | store      | Сохранение новой записи      |
| show        | GET /users/{id} | show    | Просмотр одной записи        |
| edit        | GET /users/{id}/edit | edit | Форма редактирования записи |
| update      | PUT/PATCH /users/{id} | update | Обновление записи        |
| destroy     | DELETE /users/{id} | destroy | Удаление записи           |

Пример ресурсного контроллера:

```php
class UserController extends Controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('User');
        $this->ext['users'] = $model->getAll();
    }

    public function index_app_output(): void
    {
        $this->display('index');
    }

    public function create_app_output(): void
    {
        $this->display('create');
    }

    public function store_app_data(): void
    {
        // Обработка POST-данных
        $name = $this->param('name');
        // ... сохранение
        $this->redirectTo('/users');
    }

    // и т.д.
}
```

## API-контроллеры

API-контроллеры предназначены для создания RESTful API и по умолчанию возвращают JSON. Создаются с помощью `make:controller --api`. Они не содержат методов `create` и `edit` (так как эти действия обычно не требуются в API), а вместо HTML используют JSON-ответы.

Пример API-контроллера:

```php
class UserApiController extends Controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('User');
        $this->ext['users'] = $model->getAll();
    }

    public function index_app_output(): void
    {
        $this->json($this->ext['users']);
    }
}
```

## Взаимодействие с маршрутами

Контроллеры связываются с маршрутами через JSON-конфигурацию. В маршруте указываются модуль, контроллер и действие.

Пример маршрута `app/apps/home/routes/users.json`:

```json
{
    "default": "index",
    "routes": {
        "users": {
            "module": "users",
            "controller": "users",
            "action": "index"
        },
        "users/create": {
            "module": "users",
            "controller": "users",
            "action": "create"
        }
    }
}
```

При обращении к URL `/users` будет вызван контроллер `users`, действие `index`.

## Расширенные возможности

### Наследование и кастомизация

Вы можете создавать базовые контроллеры для повторного использования общей логики. Например, `BaseController`:

```php
namespace app\home\modules\base\controller;

use Architect\Services\Mvc\Controller;

abstract class BaseController extends Controller
{
    protected function requireAuth(): void
    {
        if (!$this->get('auth')->check()) {
            $this->redirectTo('/login');
        }
    }
}
```

Затем другие контроллеры наследуются от него.

### Использование сервисов

Через метод `get()` можно получить любой сервис, зарегистрированный в контейнере:

```php
$logger = $this->get('logger');
$logger->info('Пользователь зашёл на страницу');
```

### Работа с сессиями

Сессии доступны через сервис `session`:

```php
$session = $this->get('session');
$session->set('user_id', 123);
```

### Валидация данных

Используйте сервис `validator` для проверки входящих данных:

```php
$validator = $this->get('validator');
$result = $validator->validate($_POST, [
    'email' => 'required|email',
    'password' => 'required|min:6',
]);

if (!$result->isValid()) {
    // Обработка ошибок
}
```

## Отладка контроллеров

- **Отладочная панель** — внизу страницы отображается информация о выполненных контроллерах, времени, памяти и SQL-запросах.
- **Логирование** — используйте `$this->get('logger')` для записи логов.
- **Исключения** — контроллеры автоматически перехватывают исключения и отображают страницы ошибок (404, 500 и т.д.) в соответствии с настройками.

## Пример полного контроллера

```php
<?php

declare(strict_types=1);

namespace app\home\modules\blog\controller;

use Architect\Services\Mvc\Controller;

class PostController extends Controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('Post');
        $this->ext['posts'] = $model->getPublished();
        $this->ext['title'] = 'Блог';
    }

    public function index_app_output(): void
    {
        $this->display('index');
    }

    public function show_app_data(): void
    {
        $id = (int) $this->param('id');
        $model = $this->getModel('Post');
        $post = $model->find($id);

        if (!$post) {
            $this->redirectTo('/404');
            return;
        }

        $this->ext['post'] = $post;
        $this->ext['title'] = $post['title'];
    }

    public function show_app_output(): void
    {
        $this->display('show');
    }

    public function create_app_output(): void
    {
        $this->display('create');
    }

    public function store_app_data(): void
    {
        $title = $this->param('title');
        $content = $this->param('content');

        // Сохранение в БД
        $model = $this->getModel('Post');
        $model->create(['title' => $title, 'content' => $content]);

        $this->redirectTo('/blog');
    }
}
```

## Заключение

Контроллеры в Architect RED 2 предоставляют мощный и гибкий инструмент для обработки запросов. Используя этапы жизненного цикла, встроенные методы и интеграцию с сервисами, вы можете быстро создавать сложную бизнес-логику, сохраняя код чистым и поддерживаемым.

Для дальнейшего изучения рекомендуем ознакомиться с разделами [Модели](models.md) и [Маршрутизация](routing.md).