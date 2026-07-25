# Обработка ошибок (Errors)

Компонент Errors отвечает за перехват, логирование и отображение ошибок и исключений в приложении. Он обеспечивает централизованную обработку PHP-ошибок, исключений и фатальных ошибок, а также предоставляет механизм для кастомных страниц ошибок (404, 500 и т.д.).

## Конфигурация

Настройки обработки ошибок находятся в `app/config/errors.json`:

```json
{
    "display_errors": false,
    "log_errors": true,
    "error_reporting": "E_ALL",
    "ignore_errors": ["E_NOTICE", "E_DEPRECATED"],
    "handlers": {
        "exception": true,
        "error": true,
        "shutdown": true
    },
    "pages": {
        "404": "app/modules/_404/view/_404.php",
        "500": "app/template/errors/500.php"
    }
}
```

- `display_errors` – показывать ли ошибки пользователю (в production должно быть `false`).
- `log_errors` – записывать ли ошибки в лог.
- `error_reporting` – уровень отчётов об ошибках (строка или целое число).
- `ignore_errors` – список типов ошибок, которые будут проигнорированы (не логироваться).
- `handlers` – включение обработчиков для исключений, ошибок и фатальных ошибок.
- `pages` – пути к пользовательским шаблонам страниц ошибок.

## Класс Errors

Основной класс – `Architect\Services\Errors\Errors`. Он регистрирует обработчики с помощью `set_error_handler`, `set_exception_handler` и `register_shutdown_function`.

### Инициализация

Обработчик ошибок инициализируется автоматически при загрузке сервиса (в методе `boot` ServiceProvider). Вы также можете инициализировать его вручную:

```php
$errors = $container->get('errors');
$errors->init();
```

### Методы

- `init()` – регистрирует обработчики.
- `handleException(Throwable $e)` – обрабатывает исключение.
- `handleError(int $severity, string $message, string $file, int $line)` – обрабатывает PHP-ошибку.
- `handleShutdown()` – обрабатывает фатальные ошибки.
- `setErrorPage(int $code, string $path)` – устанавливает путь к кастомной странице ошибки.
- `ignoreErrorType(int $type)` – добавляет тип ошибки в игнорируемые.

## Пользовательские страницы ошибок

### Страница 404

При отсутствии маршрута или ресурса вызывается страница 404. По умолчанию используется шаблон `app/modules/_404/view/_404.php`. Вы можете изменить его в конфигурации.

### Страница 500

При внутренней ошибке сервера (исключение) отображается страница 500. В режиме разработки вместо страницы показывается детальная информация об ошибке (stack trace).

### Создание собственной страницы ошибки

Создайте файл шаблона, например `app/template/errors/403.php`:

```php
<!DOCTYPE html>
<html>
<head>
    <title>Доступ запрещён</title>
</head>
<body>
    <h1>403 - Доступ запрещён</h1>
    <p>У вас нет прав для просмотра этой страницы.</p>
</body>
</html>
```

Затем укажите его в конфигурации:

```json
{
    "pages": {
        "403": "app/template/errors/403.php"
    }
}
```

## Логирование ошибок

Все ошибки и исключения логируются через сервис `logger`. Канал логирования определяется конфигурацией логгера (обычно `error`). Контекст логирования включает стек вызовов, файл, строку и дополнительные данные.

Пример записи в лог:

```
[2026-03-13 12:34:56] ERROR Division by zero in /app/modules/calc/controller.php:42
Stack trace:
#0 ...
```

## Интеграция с Debug Panel

При включённом Debug Panel информация об ошибках и исключениях также отображается в панели, что упрощает отладку.

## Игнорирование определённых типов ошибок

В некоторых случаях нужно игнорировать незначительные ошибки (например, `E_DEPRECATED`). Это можно сделать через конфигурацию или программно:

```php
$errors->ignoreErrorType(E_DEPRECATED);
```

## Обработка исключений в контроллерах

Вы можете перехватывать исключения внутри контроллеров с помощью try-catch, но если исключение не перехвачено, оно будет обработано глобальным обработчиком.

Пример:

```php
public function show($id)
{
    try {
        $post = $this->model->findOrFail($id);
    } catch (ModelNotFoundException $e) {
        // Показать кастомную страницу 404
        return $this->view('errors/404', [], 404);
    }
}
```

## Кастомные обработчики ошибок

Вы можете заменить стандартный обработчик, создав класс, реализующий `ErrorHandlerInterface`, и зарегистрировав его в контейнере под идентификатором `errors`.

```php
use Architect\Services\Errors\Contracts\ErrorHandlerInterface;

class MyErrorHandler implements ErrorHandlerInterface
{
    public function handleException(Throwable $e): void
    {
        // собственная логика
    }
}
```

Регистрация:

```php
$container->set('errors', new MyErrorHandler($container));
```

## Тестирование обработки ошибок

В тестах можно проверить, что при определённых условиях вызывается правильная страница ошибки или логируется исключение.

```php
public function test_404_page()
{
    $response = $this->get('/non-existent-route');
    $this->assertResponseStatus(404);
    $this->assertStringContainsString('Not Found', $response->getContent());
}
```

## Примеры

### Глобальная обработка PDOException

Добавьте в сервис-провайдер:

```php
$errors = $container->get('errors');
$errors->addHandler(PDOException::class, function($e) use ($logger) {
    $logger->critical('Database error', ['exception' => $e]);
    // отправить уведомление администратору
});
```

### Включение отображения ошибок только для разработки

В конфигурации окружения `development.json`:

```json
{
    "errors": {
        "display_errors": true,
        "log_errors": true
    }
}
```

## Заключение

Компонент Errors обеспечивает надёжную и гибкую обработку ошибок в приложении, позволяя контролировать логирование, отображение и поведение при различных типах сбоев. Правильная настройка обработки ошибок критически важна для безопасности и удобства пользователей.

Дополнительные сведения см. в [документации по обработке ошибок](../docs2/errors.md).