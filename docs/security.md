# Безопасность

Безопасность веб-приложений – критически важный аспект разработки. Architect RED 2 включает ряд встроенных механизмов защиты и предоставляет инструменты для реализации лучших практик безопасности. В этой главе описаны основные угрозы и способы их mitigation в рамках фреймворка.

## Защита от SQL-инъекций

### Использование Axiom ORM

Axiom ORM использует подготовленные выражения (prepared statements) для всех запросов, что исключает SQL-инъекции при правильном использовании.

```php
// Безопасно
$users = Orm::table('users')
    ->where('name', '=', $inputName)
    ->get();

// Также безопасно
Orm::raw('SELECT * FROM users WHERE id = ?', [$id])->get();
```

**Не используйте** конкатенацию строк для построения запросов:

```php
// ОПАСНО!
$sql = "SELECT * FROM users WHERE name = '$inputName'";
```

### Валидация и санитизация входных данных

Перед передачей в запрос убедитесь, что данные соответствуют ожидаемому формату.

```php
$id = (int) $_GET['id']; // приведение к integer
$name = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
```

## Защита от XSS (Cross-Site Scripting)

### Автоматическое экранирование в Blueprint

Blueprint по умолчанию экранирует все переменные, выводимые через `{{ ... }}`.

```blade
{{ user_input }} {# безопасно — экранируется #}
{!! raw_html !!} {# опасно — выводится как есть #}
```

Используйте `{!! ... !!}` только для доверенного HTML, который вы контролируете.

### Ручное экранирование в PHP

Если вы генерируете HTML вручную, используйте функцию `htmlspecialchars`:

```php
echo '<div>' . htmlspecialchars($userContent, ENT_QUOTES, 'UTF-8') . '</div>';
```

### Заголовки Content-Security-Policy (CSP)

Настройте CSP для ограничения источников скриптов, стилей и других ресурсов. Добавьте middleware для установки заголовков:

```php
class CspMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);
        $response->setHeader(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self' https://trusted.cdn.com;"
        );
        return $response;
    }
}
```

## Защита от CSRF (Cross-Site Request Forgery)

### Встроенная CSRF-защита

Architect включает CSRF-защиту через сервис `form`. Токен генерируется автоматически и проверяется для POST-запросов.

#### Генерация токена в форме

```php
<form method="post">
    <?= $this->form->csrfToken() ?>
    <!-- остальные поля -->
</form>
```

Или в шаблоне Blueprint:

```blade
<form method="post">
    {{ csrf_token() }}
    ...
</form>
```

#### Проверка токена

Middleware `csrf` автоматически проверяет токен для маршрутов, требующих защиты. Добавьте его в конфигурацию маршрута:

```json
{
    "route": "/submit",
    "controller": "FormController",
    "methods": ["POST"],
    "middleware": ["csrf"]
}
```

### Ручная проверка

```php
if (!$this->form->validateCsrfToken($_POST['_token'] ?? '')) {
    throw new \Exception('CSRF token mismatch');
}
```

## Защита от аутентификационных атак

### Хеширование паролей

Используйте встроенные функции PHP `password_hash` и `password_verify`.

```php
$hash = password_hash($password, PASSWORD_DEFAULT);
if (password_verify($inputPassword, $hash)) {
    // успешно
}
```

### Ограничение попыток входа

Реализуйте механизм ограничения попыток входа (rate limiting) через middleware `rate`.

```json
{
    "route": "/login",
    "controller": "AuthController",
    "middleware": ["rate:5,60"] // 5 попыток в 60 секунд
}
```

### Сессии

Используйте безопасные настройки сессий:

- `session.cookie_httponly = 1` – защита от XSS-доступа к кукам
- `session.cookie_secure = 1` (в production) – передача только по HTTPS
- `session.use_strict_mode = 1` – предотвращение фиксации сессии

Настройки можно задать в конфигурации окружения:

```json
{
    "session": {
        "name": "SECURE_SESSION",
        "cookie_httponly": true,
        "cookie_secure": true,
        "lifetime": 7200
    }
}
```

## Защита от инъекций файлов

### Валидация загружаемых файлов

- Проверяйте MIME-тип, расширение, размер.
- Сохраняйте файлы вне корня веб-сервера, если возможно.
- Генерируйте случайные имена файлов.

```php
$allowedTypes = ['image/jpeg', 'image/png'];
if (!in_array($_FILES['file']['type'], $allowedTypes)) {
    throw new \Exception('Invalid file type');
}
```

### Отключение выполнения PHP в uploads

В конфигурации веб-сервера добавьте:

```nginx
location ~* /uploads/.*\.php$ {
    deny all;
}
```

## Безопасность заголовков HTTP

### Middleware для безопасных заголовков

Добавьте middleware, который устанавливает security-заголовки:

```php
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('X-XSS-Protection', '1; mode=block');
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        return $response;
    }
}
```

Зарегистрируйте его глобально в конфигурации маршрутизации.

## Валидация входных данных

### Использование Form Validator

Сервис `form` предоставляет валидатор с правилами:

```php
$validator = $this->form->validator($_POST, [
    'email' => 'required|email',
    'password' => 'required|min:8',
    'age' => 'integer|min:18'
]);

if ($validator->fails()) {
    $errors = $validator->errors();
    // обработка ошибок
}
```

### Санитизация данных

Очищайте данные перед использованием:

```php
use Architect\Services\Form\Sanitizer;

$sanitizer = new Sanitizer();
$clean = $sanitizer->clean($_POST['text'], 'string');
```

## Защита от атак типа "Insecure Direct Object References" (IDOR)

Всегда проверяйте права доступа к объектам.

```php
public function edit(int $id)
{
    $post = $this->model->getPost($id);
    if ($post->user_id !== $this->user->id) {
        throw new \Exception('Access denied');
    }
    // ...
}
```

Используйте RBAC (Role-Based Access Control) из модуля аутентификации.

## Безопасность конфигурации

### Хранение секретов

Никогда не храните пароли, API-ключи в репозитории. Используйте переменные окружения и файл `.env`.

Пример `.env`:

```
DB_PASSWORD=secret
API_KEY=abc123
```

Загрузите их в конфигурацию:

```php
$dbPassword = getenv('DB_PASSWORD');
```

### Права доступа к файлам

Убедитесь, что файлы конфигурации и логи недоступны извне. Настройте правильные права:

```bash
chmod 640 app/config/database.json
chmod 750 app/logs
```

## Мониторинг и логирование безопасности

### Логирование подозрительных действий

Настройте логгер для записи попыток несанкционированного доступа, невалидных CSRF-токенов, частых запросов.

```php
$logger = $container->get('logger');
$logger->warning('Failed login attempt', ['ip' => $request->getIp()]);
```

### Аудит безопасности

Периодически проверяйте приложение с помощью сканеров уязвимостей (OWASP ZAP, Burp Suite) и статических анализаторов кода (PHPStan, Psalm).

## Обновления зависимостей

Регулярно обновляйте зависимости Composer, чтобы получать исправления уязвимостей.

```bash
composer update --dry-run
composer update
```

## Резюме лучших практик

1. **Всегда экранируйте вывод**.
2. **Используйте подготовленные выражения** для SQL.
3. **Валидируйте и санитизируйте все входные данные**.
4. **Применяйте CSRF-токены для изменяющих действий**.
5. **Храните пароли в хешированном виде**.
6. **Используйте HTTPS в production**.
7. **Ограничивайте права доступа** (принцип наименьших привилегий).
8. **Ведите логи безопасности**.
9. **Регулярно обновляйте фреймворк и зависимости**.
10. **Проводите аудит безопасности**.

## Заключение

Architect RED 2 предоставляет базовые механизмы безопасности, но ответственность за их правильное использование лежит на разработчике. Следуйте рекомендациям этой главы, чтобы построить безопасное и надёжное приложение.

Дополнительные сведения см. в разделах [Аутентификация](auth.md) и [Конфигурация](configuration.md).