# Axiom ORM

Universal SQL query constructor for PHP with multi-database support (MySQL, PostgreSQL, SQLite).

## Overview

Axiom ORM - это универсальный конструктор SQL-запросов для PHP с поддержкой нескольких баз данных. Может использоваться как самостоятельно, так и интегрироваться с Architect Framework.

## Core Features

- **Query Builder** - ООП-интерфейс для построения запросов
- **Multi-Database** - Поддержка MySQL, PostgreSQL, SQLite
- **Entity Support** - Маппинг результатов в объекты
- **Transactions** - Полная поддержка транзакций
- **Architect Integration** - Интеграция с Architect Framework

## Installation

```bash
composer require axiom/orm
```

## Optional Modules

| Module | Description | Install |
|--------|-------------|---------|
| `axiom/migration` | Database migrations | `composer require axiom/migration` |
| `axiom/cache` | Query caching | `composer require axiom/cache` |
| `axiom/many-to-many` | Many-to-many relations | `composer require axiom/many-to-many` |
| `axiom/entity` | Entity with PHP 8+ attributes | `composer require axiom/entity` |

## Quick Start

```php
<?php

use Axiom\Orm\Orm;
use Axiom\Orm\Connection\ConnectionManager;

// Load configuration
ConnectionManager::loadConfig(__DIR__ . '/config/database.json');

// Query builder
$users = Orm::table('users')
    ->where('status', '=', 'active')
    ->orderBy('name')
    ->limit(10)
    ->get();
```

## Configuration

Create `config/database.json`:

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
            "password": "secret",
            "charset": "utf8mb4"
        },
        "pgsql": {
            "driver": "pgsql",
            "host": "localhost",
            "port": 5432,
            "database": "myapp",
            "username": "postgres",
            "password": "secret",
            "schema": "public"
        },
        "sqlite": {
            "driver": "sqlite",
            "database": "database.sqlite"
        }
    }
}
```

## Query Builder

### Select

```php
// All columns
$users = Orm::table('users')->get();

// Specific columns
$users = Orm::table('users')
    ->select(['id', 'name', 'email'])
    ->get();

// With conditions
$user = Orm::table('users')
    ->where('id', '=', 1)
    ->first();

// With joins
$orders = Orm::table('orders')
    ->select(['orders.*', 'users.name'])
    ->join('users', 'orders.user_id', '=', 'users.id')
    ->get();
```

### Insert

```php
$id = Orm::table('users')
    ->insert('users')
    ->set([
        'name' => 'John',
        'email' => 'john@example.com',
        'created_at' => date('Y-m-d H:i:s')
    ])
    ->execute();
```

### Update

```php
$affected = Orm::table('users')
    ->update('users')
    ->set(['status' => 'inactive'])
    ->where('id', '=', 1)
    ->execute();
```

### Delete

```php
$deleted = Orm::table('users')
    ->delete('users')
    ->where('status', '=', 'banned')
    ->execute();
```

### Raw Queries

```php
$results = Orm::raw("
    SELECT * FROM users 
    WHERE created_at BETWEEN ? AND ?
", [$startDate, $endDate])->get();
```

### Aggregates

```php
$count = Orm::table('users')->count();
$count = Orm::table('users')->where('status', '=', 'active')->count();
$sum = Orm::table('orders')->sum('total');
$avg = Orm::table('products')->avg('price');
$max = Orm::table('products')->max('price');
$min = Orm::table('products')->min('price');
```

### Where Conditions

```php
// Basic
->where('status', '=', 'active')

// IN
->whereIn('role', ['admin', 'moderator'])

// BETWEEN
->whereBetween('age', [18, 65])

// NULL
->whereNull('deleted_at')
->whereNotNull('activated_at')

// Multiple OR
->where('status', '=', 'active')
->orWhere('role', '=', 'admin')

// Raw
->whereRaw('LENGTH(name) > ?', [5])
```

### Transactions

```php
Orm::transaction(function () {
    Orm::table('accounts')
        ->update('accounts')
        ->set(['balance' => 100])
        ->where('id', '=', 1)
        ->execute();
    
    Orm::table('accounts')
        ->update('accounts')
        ->set(['balance' => 50])
        ->where('id', '=', 2)
        ->execute();
});
```

## Entity Support

```php
class User
{
    public int $id;
    public string $name;
    public string $email;
    
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
}

$users = Orm::table('users')
    ->select(['id', 'name', 'email'])
    ->entity(User::class)
    ->get();

foreach ($users as $user) {
    echo $user->getName();
}
```

## Architect Framework Integration

### Option 1: Use trait in Model

```php
<?php

use Architect\Services\Mvc\ModelBase;
use Axiom\Orm\Integrations\Architect\ModelOrmTrait;

class UserModel extends ModelBase
{
    use ModelOrmTrait;
    
    public function getActiveUsers(): array
    {
        return $this->db()
            ->from('users')
            ->where('status', '=', 'active')
            ->get();
    }
}
```

### Option 2: Bootstrap ORM

In your `appbootstrap.php`:

```php
<?php

use Axiom\Orm\Integrations\Architect\OrmServiceProvider;

// Load ORM configuration
$configPath = APP_DIR . 'config/database.json';
if (file_exists($configPath)) {
    $config = json_decode(file_get_contents($configPath), true);
    OrmServiceProvider::bootstrap($config['connections'] ?? $config);
}
```

Then use via container:

```php
$db = $this->get('db');
$users = $db->from('users')->where('status', '=', 'active')->get();
```

## Available Methods

### Select Modifiers

- `select(array $columns)` - Columns to select
- `from(string $table)` - Table name
- `table(string $table)` - Alias for from()
- `join()`, `leftJoin()`, `rightJoin()` - JOINs
- `where()`, `andWhere()`, `orWhere()` - WHERE conditions
- `whereIn()`, `whereNotIn()` - IN conditions
- `whereNull()`, `whereNotNull()` - NULL checks
- `whereBetween()`, `whereNotBetween()` - BETWEEN
- `whereRaw()` - Raw WHERE
- `groupBy()` - GROUP BY
- `having()`, `havingRaw()` - HAVING
- `orderBy()` - ORDER BY
- `limit()`, `offset()` - Pagination
- `distinct()` - DISTINCT modifier

### Execution Methods

- `get()` - Get all results
- `first()` - Get first result
- `pluck(string $column)` - Get single column values
- `execute()` - Execute INSERT/UPDATE/DELETE
- `exists()` - Check if records exist

### Aggregate Functions

- `count()`, `sum()`, `avg()`, `max()`, `min()`

## Modules

### 1. Migration (`axiom/migration`)

Управление схемой базы данных через миграции:

```bash
php vendor/bin/axiom migrate          # Применить миграции
php vendor/bin/axiom rollback         # Откатить последнюю
php vendor/bin/axiom status           # Показать статус
php vendor/bin/axiom make:migration create_users_table
```

### 2. Cache (`axiom/cache`)

Кэширование результатов запросов:

```php
// Кэшировать результат на 5 минут
$users = Orm::table('users')->cache(300)->get();

// Свой ключ кэша
$users = Orm::table('users')->remember('popular_users', 600)->get();

// Отключить кэширование
$users = Orm::table('users')->disableCache()->get();
```

### 3. Many-to-Many (`axiom/many-to-many`)

Отношения "многие-ко-многим":

```php
$user->roles()->attach(3);           // Добавить связь
$user->roles()->detach(3);           // Удалить связь
$user->roles()->sync([1, 2, 3]);     // Синхронизировать
$user->roles()->toggle([1, 2]);      // Переключить
$roles = $user->roles()->get();      // Получить связанные
```

### 4. Entity (`axiom/entity`)

Сущности с PHP 8+ атрибутами:

```php
use Axiom\Entity\Annotation as ORM;

#[ORM\Entity(table: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name = '';
    
    // ... getters and setters
}

// Использование
$user = User::find(1);
$user->setName('New Name');
$user->save();
$user->delete();
```

## License

MIT
