# Сервисы

Сервисы — это основные функциональные компоненты Architect RED 2, управляемые через контейнер зависимостей. Каждый сервис реализует определённую задачу (маршрутизация, логирование, шаблонизация и т.д.) и доступен по уникальному идентификатору.

## Регистрация сервисов

Сервисы регистрируются в `ServiceProvider` (`architect/Support/ServiceProvider.php`). При создании контейнера вызывается метод `register()`, который добавляет фабрики для каждого сервиса.

```php
public function register(): void
{
    $this->container->factory('environment', fn($c) => EnvironmentManager::getInstance());
    $this->container->factory('router', fn($c) => new Router($c));
    $this->container->factory('config', fn($c) => new Config($c, 'apps'));
    // ...
}
```

## Список сервисов

| Идентификатор | Класс | Описание |
|---------------|-------|----------|
| `environment` | `EnvironmentManager` | Управление окружением и конфигурацией |
| `config` | `Config` | Конфигурация приложений |
| `logger` | `Logger` | Логирование (PSR-3) |
| `router` | `Router` | Маршрутизация URL |
| `apps` | `Apps` | Управление приложениями |
| `template` | `Template` | Шаблонизатор |
| `view` | `View` | Представление |
| `model` | `Model` | Модель |
| `language` | `Language` | Язык и переводы |
| `pattern` | `Pattern` | Обработчик MVC |
| `errors` | `Errors` | Обработка ошибок |
| `debug` | `Debug` | Отладочная панель |
| `form` | `Form` | Управление формами |
| `blueprint` | `BlueprintService` | Шаблонизатор Blueprint (если установлен) |
| `axiom` | `Orm` | ORM Axiom (если установлен) |
| `auth` | `AuthManager` | Система аутентификации (если установлен) |

## Использование сервисов

### В контроллере

Контроллеры наследуют `Architect\Services\Mvc\Controller`, который предоставляет метод `get()` для доступа к сервисам.

```php
use Architect\Services\Mvc\Controller;

class MyController extends Controller
{
    public function index_app_output(): void
    {
        $router = $this->get('router');
        $config = $this->get('config');
        
        $this->ext['title'] = 'Моя страница';
        $this->display('myview');
    }
}
```

### В модели

Модели наследуют `Architect\Services\Mvc\ModelBase`.

```php
use Architect\Services\Mvc\ModelBase;

class MyModel extends ModelBase
{
    public function doSomething(): void
    {
        $logger = $this->get('logger');
        $logger->info('Выполняем действие');
    }
}
```

### Прямой доступ через контейнер

```php
$container = Container::getInstance();
$router = $container->get('router');
```

## Описание сервисов

### Config

**Класс:** `Architect\Services\Config\Config`

Управление конфигурацией приложений. Поддерживает dot notation.

```php
$config = $container->get('config');

// Получить конфигурацию приложения
$appConfig = $config->getAppConfig();

// Получить значение
$value = $config->get('key', 'default');

// Установить значение (только для текущего запроса)
$config->set('key', 'value');
```

### Logger

**Класс:** `Architect\Services\Logger\Logger`

PSR-3 совместимый логгер. Записывает сообщения в файлы в директории `app/logs/`.

```php
$logger = $container->get('logger');

$logger->info('Информационное сообщение');
$logger->warning('Предупреждение');
$logger->error('Ошибка');
$logger->debug('Отладочная информация', ['data' => $value]);
```

### Router

**Класс:** `Architect\Services\Routing\Router`

Маршрутизация URL. Определяет модуль, контроллер и действие на основе URL и конфигурации маршрутов.

```php
$router = $container->get('router');

// Получить текущий маршрут
$module = $router->getModule();
$controller = $router->getController();
$action = $router->getAction();

// Сгенерировать URL по имени маршрута
$url = $router->generate('route_name', ['id' => 5]);
```

### Apps

**Класс:** `Architect\Services\App\Apps`

Управление приложениями. Позволяет переключаться между приложениями (home, admin и др.).

```php
$apps = $container->get('apps');

// Получить текущее приложение
$currentApp = $apps->getCurrentApp();

// Переключить приложение
$apps->switchApp('admin');

// Получить конфигурацию приложения
$appConfig = $apps->getAppConfig('home');
```

### Template

**Класс:** `Architect\Services\Template\Template`

Шаблонизатор. Управляет выбором шаблона, элементами и виджетами.

```php
$template = $container->get('template');

// Установить шаблон
$template->setTemplate('bootstrap');

// Отключить шаблон
$template->disable();

// Заблокировать изменения
$template->lock();
```

### View

**Класс:** `Architect\Services\Mvc\View`

Рендеринг представлений.

```php
$view = $container->get('view');

// Отрендерить представление
$html = $view->render('module/view', $data);
```

### Model

**Класс:** `Architect\Services\Mvc\Model`

Создание экземпляров моделей.

```php
$model = $container->get('model');

// Создать модель
$userModel = $model->create('User');
```

### Language

**Класс:** `Architect\Services\I18n\Language`

Интернационализация и переводы.

```php
$language = $container->get('language');

// Получить перевод
$translated = $language->get('key', 'module');

// Получить все переводы модуля
$all = $language->getAll('module');

// Установить язык
$language->setLanguage('ru');
```

### Pattern

**Класс:** `Architect\Services\Mvc\Pattern`

Обработчик MVC-паттерна. Связывает контроллеры, модели и представления.

```php
$pattern = $container->get('pattern');

// Обработать запрос
$pattern->run();
```

### Errors

**Класс:** `Architect\Services\Errors\Errors`

Обработка ошибок и исключений. Реализует PSR-3 и Dependency Injection.

```php
$errors = $container->get('errors');

// Инициализировать обработчики
$errors->init();

// Показать 404
$errors->display404('Страница не найдена');

// Показать ошибку
$errors->displayError('Сообщение ошибки', 500);

// Показать исключение
$errors->displayException($exception);
```

### Debug

**Класс:** `Architect\Services\Debug\Debug`

Отладочная панель с интерактивным UI.

```php
$debug = $container->get('debug');

if ($debug->isEnabled()) {
    $debug->log('Message', 'info', ['data' => $value]);
    $debug->query('SELECT * FROM users', 0.001, []);
    $debug->cacheHit('user:1');
}

$data = $debug->getData();
$debug->render(); // выводит панель
```

### Form

**Класс:** `Architect\Services\Form\Form`

Управление формами, валидация, CSRF-защита.

```php
$form = $container->get('form');

// Создать форму
$form->open('/submit', 'post');
$form->input('name', 'text', ['class' => 'form-control']);
$form->close();
```

### Blueprint

**Класс:** `Architect\Services\Blueprint\BlueprintService`

Шаблонизатор Blueprint (требует установки пакета `blueprint`).

```php
$blueprint = $container->get('blueprint');

// Рендер шаблона
$html = $blueprint->render('template', ['data' => $value]);

// Рендер строки
$html = $blueprint->renderString('Hello, {{ name }}!', ['name' => 'World']);
```

### Axiom

**Класс:** `Axiom\Orm`

ORM Axiom (требует установки пакета `axiom`).

```php
$axiom = $container->get('axiom');

// Создать QueryBuilder
$query = $axiom->table('users')->where('active', 1)->get();
```

### Auth

**Класс:** `Architect\AuthSystem\AuthManager`

Система аутентификации (требует установки пакета `architect/auth-system`).

```php
$auth = $container->get('auth');

// Аутентификация
if ($auth->login($username, $password)) {
    // успешно
}

// Проверка прав
if ($auth->hasPermission('edit_posts')) {
    // разрешено
}
```

## Создание собственного сервиса

1. Создайте класс, реализующий необходимую функциональность.
2. Добавьте зависимость от контейнера в конструктор (опционально).
3. Зарегистрируйте сервис в `ServiceProvider` или в отдельном провайдере.

**Пример:**

```php
namespace App\Services;

class MyService
{
    public function __construct(private \Architect\Core\Container $container) {}
    
    public function doSomething(): string
    {
        return 'Hello';
    }
}
```

Регистрация в `ServiceProvider`:

```php
$this->container->factory('my_service', fn($c) => new \App\Services\MyService($c));
```

Использование:

```php
$myService = $container->get('my_service');
$result = $myService->doSomething();
```

## Расширение существующих сервисов

Вы можете расширить сервисы через наследование и переопределение фабрики в контейнере. Однако рекомендуется использовать композицию и dependency injection для добавления новой функциональности.

## Заключение

Сервисы Architect RED 2 предоставляют готовые решения для типовых задач веб-разработки. Благодаря контейнеру зависимостей и чёткой архитектуре вы можете легко заменять, расширять или создавать новые сервисы в соответствии с требованиями проекта.