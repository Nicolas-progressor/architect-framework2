# Маршрутизация

Маршрутизация в Architect RED 2 связывает URL-адреса с контроллерами, действиями и модулями. Это гибкая система, которая поддерживает как конфигурацию через JSON-файлы, так и автоматическое разрешение на основе структуры URL.

## Обзор

Маршрутизатор анализирует URL запроса и определяет:

- **Модуль** — группа связанных функциональностей (например, `users`, `blog`).
- **Контроллер** — класс, обрабатывающий запрос (например, `users`, `PostController`).
- **Действие** — метод контроллера, который будет выполнен (например, `index`, `show`).
- **Параметры** — дополнительные данные, извлечённые из URL (например, ID записи).

Маршруты могут быть определены двумя способами:

1. **Явно** — через JSON-файлы маршрутов.
2. **Неявно** — через автоматическое разрешение на основе структуры папок и файлов.

## Конфигурация маршрутизатора

Настройки маршрутизатора хранятся в `app/config/router.json`:

```json
{
    "default_module": "home",
    "default_controller": "home",
    "default_action": "index",
    "404_module": "_404",
    "404_controller": "_404",
    "404_action": "index",
    "case_sensitive": false,
    "auto_resolve": true
}
```

- `default_module`, `default_controller`, `default_action` — значения по умолчанию, если маршрут не определён.
- `404_module`, `404_controller`, `404_action` — модуль, контроллер и действие для страницы 404.
- `case_sensitive` — чувствительность к регистру в URL (по умолчанию false).
- `auto_resolve` — разрешать ли маршруты автоматически на основе структуры URL (по умолчанию true).

Каждое приложение может иметь собственный файл `router.json` в папке `apps/{app}/config/router.json`, который переопределяет глобальные настройки для этого приложения. Например, приложение `admin` может задать свои значения по умолчанию и обработчик 404. Настройки мержатся рекурсивно, поэтому можно переопределить только нужные поля.

## JSON-файлы маршрутов

Маршруты определяются в JSON-файлах, которые располагаются в папках:

- **Глобальные маршруты** — `app/routes/` (доступны для всех приложений).
- **Маршруты приложения** — `app/apps/{app}/config/routes.json` (основной файл маршрутов приложения) и `app/apps/{app}/routes/` (дополнительные маршруты).
- **Маршруты модуля** — `app/apps/{app}/modules/{module}/routes/` (только для данного модуля).

Файлы могут иметь любое имя, но рекомендуется называть их по имени модуля или функциональности (например, `users.json`, `api.json`).

### Приоритет загрузки маршрутов

Маршруты загружаются в следующем порядке (последующие переопределяют предыдущие в случае конфликта ключей):

1. Глобальные маршруты (`app/routes/`)
2. Основной файл конфигурации приложения (`app/apps/{app}/config/routes.json`)
3. Дополнительные маршруты приложения (`app/apps/{app}/routes/`)
4. Маршруты модуля (`app/apps/{app}/modules/{currentModule}/routes/`)

Таким образом, маршруты из папки `routes/` могут переопределять маршруты из `config/routes.json`, а маршруты модуля имеют наивысший приоритет.

### Структура файла маршрутов

```json
{
    "default": "index",
    "routes": {
        "users": {
            "module": "users",
            "controller": "users",
            "action": "index"
        },
        "users/profile": {
            "module": "users",
            "controller": "users",
            "action": "profile"
        },
        "users/show/:id": {
            "module": "users",
            "controller": "users",
            "action": "show",
            "var_remap": ["id"]
        },
        "admin": {
            "app": "admin",
            "module": "dashboard",
            "controller": "dashboard",
            "action": "index"
        }
    }
}
```

### Ключевые поля маршрута

| Поле | Описание | Пример |
|------|----------|--------|
| `module` | Имя модуля (обязательно) | `"users"` |
| `controller` | Имя контроллера (обязательно) | `"users"` |
| `action` | Имя действия (обязательно) | `"index"` |
| `app` | Имя приложения (если маршрут должен переключить приложение) | `"admin"` |
| `template` | Имя шаблона (layout) для этого маршрута | `"layouts/admin"` |
| `notemplate` | Если `true`, шаблон не будет использоваться | `true` |
| `var_remap` | Маппинг сегментов URL на имена параметров | `["id", "slug"]` |

### Параметры в URL

Вы можете определять параметры в URL с помощью префикса `:` (двоеточие). Например, маршрут `"users/show/:id"` соответствует URL `/users/show/123`, где `123` будет доступно как параметр `id`.

Параметры также можно извлекать с помощью `var_remap`. Например:

```json
"users/edit/:id/:tab": {
    "module": "users",
    "controller": "users",
    "action": "edit",
    "var_remap": ["id", "tab"]
}
```

При запросе `/users/edit/5/profile` в контроллере будут доступны:

```php
$id = $this->param('id');   // "5"
$tab = $this->param('tab'); // "profile"
```

### Маршруты с условиями

Маршруты могут включать условия на HTTP-метод, домен и другие атрибуты (если расширено). В текущей версии поддержка условий ограничена, но вы можете использовать middleware для аналогичной функциональности.

## Автоматическое разрешение маршрутов

Если `auto_resolve` включён (по умолчанию) и для URL не найден явный маршрут, маршрутизатор пытается определить модуль, контроллер и действие на основе структуры URL.

Правила автоматического разрешения:

1. **Пустой URL** (`/`) → используется маршрут по умолчанию (`default_module`, `default_controller`, `default_action`).
2. **Один сегмент** (`/users`) → интерпретируется как модуль, контроллер = модуль, действие = `index`.
3. **Два сегмента** (`/users/profile`) → модуль = первый сегмент, контроллер = первый сегмент, действие = второй сегмент.
4. **Три сегмента** (`/users/profile/edit`) → модуль = первый сегмент, контроллер = второй сегмент, действие = третий сегмент.

Если контроллер не существует в указанном модуле, маршрутизатор пытается интерпретировать первый сегмент как контроллер в модуле по умолчанию.

### Примеры автоматического разрешения

| URL | Модуль | Контроллер | Действие | Примечание |
|-----|--------|------------|----------|------------|
| `/` | `home` | `home` | `index` | По умолчанию |
| `/users` | `users` | `users` | `index` | |
| `/users/profile` | `users` | `users` | `profile` | |
| `/users/profile/edit` | `users` | `profile` | `edit` | |
| `/blog/post/123` | `blog` | `post` | `123` | Действие = "123" (будет перехвачено как параметр) |
| `/admin` | `admin` | `admin` | `index` | Если существует модуль `admin` |

## Работа с несколькими приложениями

Architect RED 2 поддерживает несколько приложений в одном проекте. Приложения изолированы друг от друга и имеют собственные маршруты, модули и шаблоны.

### Переключение приложений через маршрут

В маршруте можно указать поле `app`, чтобы переключить текущее приложение:

```json
"admin": {
    "app": "admin",
    "module": "dashboard",
    "controller": "dashboard",
    "action": "index"
}
```

При обращении к `/admin` будет активировано приложение `admin`, и дальнейшая маршрутизация будет происходить внутри этого приложения.

### URL с префиксом приложения

Если приложение имеет префикс в URL (например, все URL начинаются с `/admin`), вы можете определить маршруты в папке `app/apps/admin/routes/` без явного указания `app`. Маршрутизатор автоматически определит приложение по первому сегменту URL.

## Параметры маршрутов в контроллерах

В контроллере вы можете получить параметры маршрута с помощью методов:

- `$this->param(string $name, string $default = '')` — возвращает значение параметра.
- `$this->segment(int $index, string $default = '')` — возвращает сегмент URL по индексу (1‑based).

Пример:

```php
public function show_app_data(): void
{
    $id = (int) $this->param('id');
    $user = $this->getModel('User')->findById($id);
    $this->ext['user'] = $user;
}
```

## Middleware для маршрутов

Вы можете прикреплять middleware к маршрутам через конфигурацию JSON (если расширено) или непосредственно в контроллере. В текущей версии рекомендуется использовать middleware на уровне контроллера.

Пример добавления middleware в контроллере:

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

## Страница 404

Если маршрут не найден, маршрутизатор перенаправляет запрос на страницу 404. По умолчанию используется модуль `_404`, контроллер `_404`, действие `index`.

Вы можете настроить свой обработчик 404 в `app/config/router.json`:

```json
{
    "404_module": "errors",
    "404_controller": "errors",
    "404_action": "notFound"
}
```

Также вы можете создать собственный модуль `_404` с контроллером и представлением для кастомной страницы ошибки.

## Генерация URL

Для генерации URL внутри приложения используйте хелпер `url()` (если доступен) или формируйте вручную.

Пример в представлении Blueprint:

```blade
<a href="{{ url('users/profile', {id: user.id}) }}">Профиль</a>
```

В PHP-шаблоне:

```php
<a href="/users/profile/<?= $user['id'] ?>">Профиль</a>
```

## Кэширование маршрутов

Для повышения производительности маршруты могут быть закэшированы. Используйте команду:

```bash
php arc cache:clear --routes
```

Чтобы очистить кэш маршрутов, или:

```bash
php arc config:cache
```

Чтобы закэшировать всю конфигурацию, включая маршруты.

## Примеры

### Пример 1: Блог с статьями

**Маршруты (`app/apps/home/routes/blog.json`):**

```json
{
    "default": "index",
    "routes": {
        "blog": {
            "module": "blog",
            "controller": "blog",
            "action": "index"
        },
        "blog/post/:slug": {
            "module": "blog",
            "controller": "blog",
            "action": "post",
            "var_remap": ["slug"]
        },
        "blog/category/:category": {
            "module": "blog",
            "controller": "blog",
            "action": "category",
            "var_remap": ["category"]
        }
    }
}
```

**Контроллер (`app/apps/home/modules/blog/controller/blog.php`):**

```php
class blog extends Controller
{
    public function index_app_data(): void
    {
        $model = $this->getModel('Post');
        $this->ext['posts'] = $model->getLatest();
    }

    public function post_app_data(): void
    {
        $slug = $this->param('slug');
        $model = $this->getModel('Post');
        $this->ext['post'] = $model->findBySlug($slug);
    }
}
```

### Пример 2: RESTful API

**Маршруты (`app/routes/api.json`):**

```json
{
    "routes": {
        "api/v1/users": {
            "module": "api",
            "controller": "UserController",
            "action": "index"
        },
        "api/v1/users/:id": {
            "module": "api",
            "controller": "UserController",
            "action": "show",
            "var_remap": ["id"]
        }
    }
}
```

**Контроллер (`app/modules/api/controller/UserController.php`):**

```php
class UserController extends Controller
{
    public function index_app_output(): void
    {
        $model = $this->getModel('User');
        $users = $model->getAll();
        $this->json($users);
    }

    public function show_app_output(): void
    {
        $id = (int) $this->param('id');
        $model = $this->getModel('User');
        $user = $model->findById($id);
        $this->json($user ?: ['error' => 'Not found'], $user ? 200 : 404);
    }
}
```

## Отладка маршрутов

Чтобы увидеть все зарегистрированные маршруты, используйте консольную команду:

```bash
php arc route:list
```

Вывод будет содержать таблицу с маршрутами, соответствующими модулями, контроллерами и действиями.

Также в отладочной панели на вкладке "Routing" отображается информация о текущем маршруте, параметрах и времени разрешения.

## Заключение

Маршрутизация в Architect RED 2 предоставляет гибкость как для простых проектов (через автоматическое разрешение), так и для сложных (через детальную JSON-конфигурацию). Используйте явные маршруты для важных URL, чтобы обеспечить предсказуемость и контроль, а автоматическое разрешение — для быстрого прототипирования.

Для более глубокого изучения рекомендуем ознакомиться с исходным кодом `Router` и `RouteLoader`, а также с разделом [Контроллеры](controllers.md).