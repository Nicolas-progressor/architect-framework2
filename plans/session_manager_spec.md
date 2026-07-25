# Техническая спецификация менеджера сессий

## Обзор
Менеджер сессий - это компонент, который предоставляет унифицированный интерфейс для работы с сессиями с поддержкой различных драйверов хранения, flash-данных и сессионных массивов.

## Архитектура

### Основные классы

#### 1. SessionManager
Основной класс для управления сессиями.

```php
<?php

namespace Architect\Session;

class SessionManager
{
    /**
     * Создает экземпляр менеджера сессий
     *
     * @param array $config Конфигурация сессий
     */
    public function __construct(array $config = []);
    
    /**
     * Получает драйвер сессий
     *
     * @param string|null $driver Название драйвера
     * @return SessionInterface
     */
    public function driver(string $driver = null): SessionInterface;
    
    /**
     * Расширяет менеджер кастомным драйвером
     *
     * @param string $name Название драйвера
     * @param callable $callback Фабрика драйвера
     * @return self
     */
    public function extend(string $name, callable $callback): self;
    
    /**
     * Получает имя драйвера по умолчанию
     *
     * @return string
     */
    public function getDefaultDriver(): string;
}
```

#### 2. SessionInterface
Интерфейс для драйверов сессий.

```php
<?php

namespace Architect\Session\Contracts;

interface SessionInterface
{
    /**
     * Запускает сессию
     *
     * @return void
     */
    public function start(): void;
    
    /**
     * Получает идентификатор сессии
     *
     * @return string
     */
    public function getId(): string;
    
    /**
     * Устанавливает идентификатор сессии
     *
     * @param string $id
     * @return void
     */
    public function setId(string $id): void;
    
    /**
     * Получает имя сессии
     *
     * @return string
     */
    public function getName(): string;
    
    /**
     * Устанавливает имя сессии
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name): void;
    
    /**
     * Проверяет, существует ли ключ в сессии
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;
    
    /**
     * Получает значение из сессии
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null);
    
    /**
     * Устанавливает значение в сессии
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value): void;
    
    /**
     * Удаляет значение из сессии
     *
     * @param string $key
     * @return void
     */
    public function remove(string $key): void;
    
    /**
     * Очищает все данные сессии
     *
     * @return void
     */
    public function clear(): void;
    
    /**
     * Получает все данные сессии
     *
     * @return array
     */
    public function all(): array;
    
    /**
     * Сохраняет сессию
     *
     * @return void
     */
    public function save(): void;
    
    /**
     * Закрывает сессию
     *
     * @return void
     */
    public function close(): void;
    
    /**
     * Уничтожает сессию
     *
     * @return void
     */
    public function destroy(): void;
    
    /**
     * Добавляет flash-данные
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function flash(string $key, $value): void;
    
    /**
     * Получает flash-данные и удаляет их
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function pull(string $key, $default = null);
    
    /**
     * Получает все flash-данные
     *
     * @return array
     */
    public function getFlashData(): array;
    
    /**
     * Рефрешит flash-данные для следующего запроса
     *
     * @return void
     */
    public function reflash(): void;
    
    /**
     * Рефрешит определенные flash-данные
     *
     * @param array $keys
     * @return void
     */
    public function keep(array $keys = []): void;
}
```

#### 3. Store
Базовая реализация сессии.

```php
<?php

namespace Architect\Session;

class Store implements Contracts\SessionInterface
{
    protected string $name;
    protected SessionHandlerInterface $handler;
    protected array $data = [];
    protected array $flashData = [];
    protected array $newFlashData = [];
    protected array $oldFlashData = [];
    protected bool $started = false;
    
    public function __construct(string $name, SessionHandlerInterface $handler, array $data = [])
    {
        $this->name = $name;
        $this->handler = $handler;
        $this->data = $data;
    }
    
    // Реализация всех методов интерфейса...
}
```

## Драйверы сессий

### 1. FileSessionHandler
Хранение сессий в файлах.

```php
<?php

namespace Architect\Session\Handlers;

class FileSessionHandler implements SessionHandlerInterface
{
    protected string $path;
    protected int $minutes;
    
    public function __construct(string $path, int $minutes = 120)
    {
        $this->path = $path;
        $this->minutes = $minutes;
    }
    
    // Реализация SessionHandlerInterface...
}
```

### 2. DatabaseSessionHandler
Хранение сессий в базе данных.

```php
<?php

namespace Architect\Session\Handlers;

class DatabaseSessionHandler implements SessionHandlerInterface
{
    protected ConnectionInterface $connection;
    protected string $table;
    protected int $minutes;
    
    public function __construct(ConnectionInterface $connection, string $table, int $minutes = 120)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->minutes = $minutes;
    }
    
    // Реализация SessionHandlerInterface...
}
```

### 3. RedisSessionHandler
Хранение сессий в Redis.

```php
<?php

namespace Architect\Session\Handlers;

class RedisSessionHandler implements SessionHandlerInterface
{
    protected Redis $redis;
    protected string $prefix;
    protected int $minutes;
    
    public function __construct(Redis $redis, string $prefix = 'session:', int $minutes = 120)
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
        $this->minutes = $minutes;
    }
    
    // Реализация SessionHandlerInterface...
}
```

### 4. ArraySessionHandler
Хранение сессий в памяти (для тестирования).

```php
<?php

namespace Architect\Session\Handlers;

class ArraySessionHandler implements SessionHandlerInterface
{
    protected array $storage = [];
    protected int $minutes;
    
    public function __construct(int $minutes = 120)
    {
        $this->minutes = $minutes;
    }
    
    // Реализация SessionHandlerInterface...
}
```

## Конфигурация

Файл конфигурации `app/config/session.json`:

```json
{
    "driver": "file",
    "lifetime": 120,
    "expire_on_close": false,
    "encrypt": false,
    "files": "/tmp/architect_sessions",
    "connection": null,
    "table": "sessions",
    "store": null,
    "lottery": [2, 100],
    "cookie": "architect_session",
    "path": "/",
    "domain": null,
    "secure": false,
    "http_only": true,
    "same_site": "lax"
}
```

## Интеграция с HTTP

### SessionMiddleware
Middleware для автоматического запуска сессий.

```php
<?php

namespace Architect\Session\Middleware;

class StartSessionMiddleware
{
    protected SessionManager $manager;
    
    public function __construct(SessionManager $manager)
    {
        $this->manager = $manager;
    }
    
    public function handle(Request $request, callable $next)
    {
        $session = $this->manager->driver();
        $session->start();
        
        $response = $next($request);
        
        $session->save();
        
        return $response;
    }
}
```

### SessionServiceProvider
Сервис-провайдер для регистрации менеджера сессий.

```php
<?php

namespace Architect\Session\Providers;

use Architect\Contracts\ServiceProviderInterface;
use Architect\Core\Contracts\ContainerInterface;

class SessionServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton('session', function ($container) {
            return new \Architect\Session\SessionManager($container);
        });
        
        $container->singleton('session.store', function ($container) {
            return $container->get('session')->driver();
        });
    }
    
    public function boot(ContainerInterface $container): void
    {
        // Регистрация middleware
        $container->get('router')->aliasMiddleware('session', \Architect\Session\Middleware\StartSessionMiddleware::class);
    }
}
```

## Использование

### Базовое использование

```php
// Получение сессии
$session = app('session.store');

// Установка значения
$session->set('key', 'value');

// Получение значения
$value = $session->get('key', 'default');

// Проверка существования
if ($session->has('key')) {
    // ...
}

// Удаление значения
$session->remove('key');

// Flash-данные
$session->flash('status', 'Task was successful!');

// Получение flash-данных
$status = $session->get('status');
```

### В контроллерах

```php
class UserController extends Controller
{
    public function store(Request $request)
    {
        // Валидация...
        
        // Сохранение flash-сообщения
        session()->flash('success', 'User created successfully!');
        
        return redirect('/users');
    }
}
```

## Безопасность

### Шифрование сессий
Опциональное шифрование данных сессии.

### HTTP Only
Защита от XSS атак через установку флага HttpOnly для cookie.

### Secure Cookie
Установка флага Secure для cookie при использовании HTTPS.

## Производительность

### Очистка устаревших сессий
Регулярная очистка устаревших файлов сессий через lottery-систему.

### Кэширование
Кэширование данных сессии в памяти во время запроса.

## Тестирование

### Unit-тесты
- Тестирование каждого драйвера
- Тестирование flash-данных
- Тестирование методов сессии

### Интеграционные тесты
- Тестирование интеграции с HTTP-запросами
- Тестирование middleware
- Тестирование различных драйверов

## Совместимость

### Существующая система
- Интеграция с существующей системой аутентификации
- Совместимость с текущими формами
- Поддержка существующих cookie-параметров

### Обратная совместимость
- Поддержка старого SessionStorage (для постепенной миграции)
- Совместимость с существующими методами работы с сессиями