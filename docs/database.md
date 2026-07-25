# Работа с базой данных

Architect Framework включает встроенный сервис **Connect** для прямого взаимодействия с реляционными базами данных через PDO. Этот сервис предоставляет простой и удобный API для выполнения SQL‑запросов, управления транзакциями и логгирования, не требуя использования полноценной ORM.

## Быстрый старт

### 1. Настройка конфигурации

Создайте файл `app/config/database.json` со следующей структурой:

```json
{
    "default": "mysql",
    "connections": {
        "mysql": {
            "driver": "mysql",
            "host": "localhost",
            "port": 3306,
            "database": "myapp",
            "username": "root",
            "password": "",
            "charset": "utf8mb4",
            "collation": "utf8mb4_unicode_ci",
            "prefix": "",
            "options": {}
        },
        "sqlite": {
            "driver": "sqlite",
            "database": "database/database.sqlite"
        }
    }
}
```

- **default** – имя соединения, используемое по умолчанию.
- **connections** – объект, где каждый ключ – имя соединения, а значение – параметры подключения.

Поддерживаемые драйверы: `mysql`, `pgsql`, `sqlite`. Для каждого драйвера параметры могут отличаться (см. [Подробности конфигурации](#подробности-конфигурации)).

### 2. Использование фасада DB

Самый простой способ выполнить запрос – использовать фасад `DB`:

```php
use Architect\Statics\DB;

// Выборка всех записей из таблицы users
$users = DB::query('SELECT * FROM users')->fetchAll();

// Вставка записи
$id = DB::execute('INSERT INTO users (name, email) VALUES (?, ?)', ['John', 'john@example.com']);

// Получение одной записи
$user = DB::query('SELECT * FROM users WHERE id = ?', [1])->fetch();
```

### 3. Получение соединения по имени

Если у вас несколько соединений, вы можете получить конкретное:

```php
$pgsql = DB::connection('pgsql');
$result = $pgsql->query('SELECT * FROM posts')->fetchAll();
```

## Основные методы

### query(string $sql, array $bindings = []): StatementInterface

Выполняет SQL‑запрос с параметрами и возвращает объект `StatementInterface`, из которого можно получить данные.

```php
$statement = DB::query('SELECT * FROM products WHERE price > ?', [100]);
$products = $statement->fetchAll();
```

### execute(string $sql, array $bindings = []): int

Выполняет запрос (INSERT, UPDATE, DELETE) и возвращает количество затронутых строк.

```php
$affected = DB::execute('UPDATE users SET active = 1 WHERE last_login > ?', ['2025-01-01']);
```

### fetch(string $sql, array $bindings = []): ?array

Выполняет запрос и возвращает первую строку результата в виде ассоциативного массива, либо `null`, если строк нет.

```php
$user = DB::fetch('SELECT * FROM users WHERE email = ?', ['admin@example.com']);
```

### fetchAll(string $sql, array $bindings = []): array

Возвращает все строки результата в виде массива ассоциативных массивов.

```php
$allUsers = DB::fetchAll('SELECT * FROM users ORDER BY created_at DESC');
```

### getPdo(): PDO

Возвращает экземпляр PDO текущего соединения для низкоуровневых операций.

```php
$pdo = DB::getPdo();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

## Транзакции

Сервис поддерживает вложенные транзакции через стандартный PDO.

### beginTransaction()

Начинает новую транзакцию (или увеличивает уровень вложенности).

```php
DB::beginTransaction();
```

### commit()

Фиксирует транзакцию. Если транзакций несколько, уменьшает уровень вложенности.

```php
DB::commit();
```

### rollBack()

Откатывает транзакцию. Если транзакций несколько, откатывает до предыдущего уровня.

```php
DB::rollBack();
```

### Пример

```php
try {
    DB::beginTransaction();

    DB::execute('INSERT INTO orders (user_id, total) VALUES (?, ?)', [1, 99.99]);
    $orderId = DB::getPdo()->lastInsertId();
    DB::execute('INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)', [$orderId, 5, 2]);

    DB::commit();
} catch (Exception $e) {
    DB::rollBack();
    throw $e;
}
```

## Работа с несколькими соединениями

### Менеджер соединений

Сервис автоматически управляет пулом соединений. Вы можете получить менеджер через контейнер:

```php
$manager = container('database'); // или container('db')
```

### Список всех соединений

```php
$connections = $manager->getConnections(); // массив имён
```

### Установка соединения по умолчанию

Изменить соединение по умолчанию можно динамически:

```php
$manager->setDefaultConnection('sqlite');
```

## Логгирование запросов

Сервис автоматически логирует все выполняемые SQL‑запросы, если включён Debug‑сервис. Запросы отображаются в панели отладки Architect.

### Настройка собственного логгера

Вы можете реализовать интерфейс `QueryLoggerInterface` и установить логгер через менеджер:

```php
use Architect\Services\Database\Contracts\QueryLoggerInterface;

class MyQueryLogger implements QueryLoggerInterface
{
    public function logQuery(string $sql, float $duration, array $bindings = []): void
    {
        // Запись в файл, отправка в Sentry и т.д.
        error_log(sprintf('[SQL] %s (%.2f ms)', $sql, $duration * 1000));
    }
}

$manager = container('database');
$manager->setQueryLogger(new MyQueryLogger());
```

## Подробности конфигурации

### Параметры соединения

| Параметр | Обязательный | Описание | Пример |
|----------|--------------|----------|--------|
| `driver` | Да | Драйвер PDO (`mysql`, `pgsql`, `sqlite`) | `"mysql"` |
| `host`   | Для mysql/pgsql | Хост БД | `"localhost"` |
| `port`   | Нет | Порт (по умолчанию: 3306 для MySQL, 5432 для PostgreSQL) | `3306` |
| `database` | Да | Имя базы данных (для sqlite – путь к файлу) | `"myapp"` |
| `username` | Для mysql/pgsql | Имя пользователя | `"root"` |
| `password` | Для mysql/pgsql | Пароль | `""` |
| `charset`  | Нет | Кодировка соединения (по умолчанию `utf8mb4`) | `"utf8mb4"` |
| `collation`| Нет | Коллация (только MySQL) | `"utf8mb4_unicode_ci"` |
| `prefix`   | Нет | Префикс таблиц (не используется напрямую) | `"arc_"` |
| `options`  | Нет | Дополнительные опции PDO (массив) | `{ "PDO::ATTR_PERSISTENT": true }` |

### Примеры конфигураций

**MySQL с кастомными опциями:**

```json
{
    "default": "mysql",
    "connections": {
        "mysql": {
            "driver": "mysql",
            "host": "127.0.0.1",
            "port": 3306,
            "database": "production",
            "username": "app_user",
            "password": "secret",
            "charset": "utf8mb4",
            "options": {
                "PDO::ATTR_PERSISTENT": true,
                "PDO::ATTR_TIMEOUT": 5
            }
        }
    }
}
```

**SQLite в памяти:**

```json
{
    "default": "sqlite",
    "connections": {
        "sqlite": {
            "driver": "sqlite",
            "database": ":memory:"
        }
    }
}
```

**PostgreSQL:**

```json
{
    "default": "pgsql",
    "connections": {
        "pgsql": {
            "driver": "pgsql",
            "host": "localhost",
            "port": 5432,
            "database": "myapp",
            "username": "postgres",
            "password": "postgres",
            "charset": "utf8"
        }
    }
}
```

## Интеграция с контейнером зависимостей

Сервис автоматически регистрируется в контейнере при загрузке `DatabaseServiceProvider`. Вы можете получить его через:

```php
$db = container('database'); // экземпляр DatabaseManager
$db = container('db');       // алиас
```

Если вам нужно прямое подключение к определённой БД, вы можете внедрить `DatabaseInterface`:

```php
use Architect\Services\Database\Contracts\DatabaseInterface;

class UserRepository
{
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function findAll()
    {
        return $this->db->fetchAll('SELECT * FROM users');
    }
}
```

Контейнер автоматически внедрит соединение по умолчанию.

## Отладка и ошибки

### Исключения

Все ошибки PDO преобразуются в исключения `PDOException`. Рекомендуется обрабатывать их в try‑catch.

```php
try {
    DB::query('SELECT * FROM non_existent_table');
} catch (PDOException $e) {
    echo 'Ошибка БД: ' . $e->getMessage();
}
```

### Просмотр выполненных запросов

Включите Debug‑панель (если она активирована в конфигурации). Все SQL‑запросы будут отображаться в разделе **Database**.

## Совместное использование с Axiom ORM

Сервис Connect и Axiom ORM могут работать параллельно. Axiom использует те же PDO‑соединения, поэтому вы можете смешивать raw‑запросы через `DB` с ORM‑моделями.

```php
use Axiom\Model;

class User extends Model
{
    protected $table = 'users';
}

// ORM
$user = User::find(1);

// Raw‑запрос
$rawData = DB::fetch('SELECT * FROM users WHERE id = ?', [1]);
```

## Часто задаваемые вопросы

### Как изменить конфигурацию в runtime?

Используйте менеджер:

```php
$manager = container('database');
$manager->addConnection('new_conn', [
    'driver' => 'mysql',
    'host' => 'another.host',
    // ...
]);
$manager->setDefaultConnection('new_conn');
```

### Поддерживает ли сервис подготовленные выражения?

Да, все методы (`query`, `execute`, `fetch`, `fetchAll`) автоматически используют подготовленные выражения для переданных параметров.

### Можно ли использовать свои классы Statement?

Нет, сервис возвращает стандартный `PDOStatement`, обёрнутый в `StatementInterface`. Вы можете расширить функциональность, создав собственный класс `Database` и зарегистрировав его в контейнере.

### Как отключить логгирование запросов?

Установите `null` в качестве логгера:

```php
$manager = container('database');
$manager->setQueryLogger(null);
```

## Заключение

Сервис Connect предоставляет простой, но мощный инструмент для работы с базами данных в Architect Framework. Он идеально подходит для проектов, где не требуется полноценная ORM, либо где нужен прямой контроль над SQL‑запросами.

Для более сложных сценариев (отношения, маппинг объектов, репозитории) рекомендуется использовать [Axiom ORM](../axiom/README.md).
