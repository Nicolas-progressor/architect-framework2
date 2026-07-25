# Модели

Модели представляют бизнес-логику и данные вашего приложения. В Architect RED 2 модели могут быть двух типов:

1. **Базовые модели** — простые PHP-классы, наследуемые от `ModelBase`, которые предоставляют доступ к контейнеру зависимостей и могут содержать произвольные методы для работы с данными.
2. **ORM-модели** — используют мощный конструктор запросов **Axiom ORM** для взаимодействия с базой данных. Axiom предоставляет богатый API для построения SQL-запросов, транзакций, миграций и работы с сущностями.

В этом разделе вы узнаете, как создавать и использовать оба типа моделей.

## Базовые модели

### Создание базовой модели

Базовые модели располагаются в папке `model/` внутри модуля. Имя файла должно соответствовать имени класса (например, `User.php`). Класс должен наследовать `Architect\Services\Mvc\ModelBase`.

**Пример модели `app/apps/home/modules/users/model/User.php`:**

```php
<?php

declare(strict_types=1);

namespace app\home\modules\users\model;

use Architect\Services\Mvc\ModelBase;

class User extends ModelBase
{
    public function getAll(): array
    {
        // Простой пример — возвращаем статические данные
        return [
            ['id' => 1, 'name' => 'Иван'],
            ['id' => 2, 'name' => 'Мария'],
        ];
    }

    public function findById(int $id): ?array
    {
        $users = $this->getAll();
        foreach ($users as $user) {
            if ($user['id'] === $id) {
                return $user;
            }
        }
        return null;
    }
}
```

### Использование модели в контроллере

Контроллер может загрузить модель с помощью метода `getModel()`:

```php
$model = $this->getModel('User');
$users = $model->getAll();
```

Или через сервис `model`:

```php
$model = $this->get('model')->create('User');
```

### Преимущества базовых моделей

- Простота — не требуют настройки базы данных.
- Полный контроль над логикой.
- Идеально подходят для работы с внешними API, файлами, кэшем и т.д.

## ORM Axiom

Axiom — это полнофункциональный ORM-конструктор запросов, встроенный в Architect RED 2. Он поддерживает MySQL, PostgreSQL, SQLite и предоставляет удобный объектно-ориентированный интерфейс для работы с базой данных.

### Настройка Axiom

Перед использованием Axiom необходимо настроить подключение к базе данных. Конфигурация хранится в `app/config/database.json`:

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
        "sqlite": {
            "driver": "sqlite",
            "database": "database/database.sqlite"
        }
    }
}
```

Также убедитесь, что в `app/config/config.json` включена поддержка базы данных:

```json
{
    "database": {
        "enabled": true,
        "default": "mysql"
    }
}
```

### Использование Axiom в моделях

Для удобства интеграции Axiom предоставляет трейт `ModelOrmTrait`, который добавляет метод `db()` для доступа к конструктору запросов.

**Пример модели с использованием Axiom:**

```php
<?php

declare(strict_types=1);

namespace app\home\modules\users\model;

use Architect\Services\Mvc\ModelBase;
use Axiom\Orm\Integrations\Architect\ModelOrmTrait;

class User extends ModelBase
{
    use ModelOrmTrait;

    public function getActiveUsers(): array
    {
        return $this->db()
            ->from('users')
            ->where('status', '=', 'active')
            ->orderBy('name')
            ->get();
    }

    public function findById(int $id): ?object
    {
        return $this->db()
            ->from('users')
            ->where('id', '=', $id)
            ->first();
    }

    public function createUser(array $data): int
    {
        return $this->db()
            ->insert('users')
            ->set($data)
            ->execute();
    }

    public function updateUser(int $id, array $data): int
    {
        return $this->db()
            ->update('users')
            ->set($data)
            ->where('id', '=', $id)
            ->execute();
    }

    public function deleteUser(int $id): int
    {
        return $this->db()
            ->delete('users')
            ->where('id', '=', $id)
            ->execute();
    }
}
```

### Прямое использование Axiom через контейнер

Вы также можете получить экземпляр Axiom напрямую из контейнера:

```php
$db = $this->get('db'); // или $this->get('axiom')
$users = $db->from('users')->where('status', '=', 'active')->get();
```

## Конструктор запросов Axiom

Axiom предоставляет fluent-интерфейс для построения SQL-запросов. Вот основные возможности.

### SELECT

```php
// Получить все записи
$users = $db->from('users')->get();

// Выбрать конкретные столбцы
$users = $db->from('users')
    ->select(['id', 'name', 'email'])
    ->get();

// Условия WHERE
$user = $db->from('users')
    ->where('id', '=', 1)
    ->first();

// Несколько условий
$users = $db->from('users')
    ->where('status', '=', 'active')
    ->where('age', '>', 18)
    ->get();

// WHERE IN
$users = $db->from('users')
    ->whereIn('role', ['admin', 'moderator'])
    ->get();

// WHERE BETWEEN
$users = $db->from('users')
    ->whereBetween('created_at', ['2024-01-01', '2024-12-31'])
    ->get();

// WHERE NULL
$users = $db->from('users')
    ->whereNull('deleted_at')
    ->get();

// Сортировка и лимит
$users = $db->from('users')
    ->orderBy('name', 'ASC')
    ->limit(10)
    ->offset(20)
    ->get();
```

### JOIN

```php
$orders = $db->from('orders')
    ->select(['orders.*', 'users.name'])
    ->join('users', 'orders.user_id', '=', 'users.id')
    ->get();

// LEFT JOIN
$products = $db->from('products')
    ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
    ->get();
```

### INSERT

```php
$id = $db->insert('users')
    ->set([
        'name' => 'Алексей',
        'email' => 'alex@example.com',
        'created_at' => date('Y-m-d H:i:s')
    ])
    ->execute();
```

### UPDATE

```php
$affected = $db->update('users')
    ->set(['status' => 'inactive'])
    ->where('id', '=', 5)
    ->execute();
```

### DELETE

```php
$deleted = $db->delete('users')
    ->where('status', '=', 'banned')
    ->execute();
```

### Агрегатные функции

```php
$count = $db->from('users')->count();
$sum = $db->from('orders')->sum('total');
$avg = $db->from('products')->avg('price');
$max = $db->from('products')->max('price');
$min = $db->from('products')->min('price');
```

### Группировка и having

```php
$stats = $db->from('orders')
    ->select(['user_id', 'COUNT(*) as order_count'])
    ->groupBy('user_id')
    ->having('order_count', '>', 5)
    ->get();
```

### Сырые запросы

```php
$results = $db->raw("
    SELECT * FROM users 
    WHERE created_at BETWEEN ? AND ?
", [$startDate, $endDate])->get();
```

## Транзакции

Axiom поддерживает транзакции для обеспечения целостности данных:

```php
use Axiom\Orm\Orm;

Orm::transaction(function () {
    $db = Orm::table('accounts');
    
    $db->update('accounts')
        ->set(['balance' => 100])
        ->where('id', '=', 1)
        ->execute();
    
    $db->update('accounts')
        ->set(['balance' => 50])
        ->where('id', '=', 2)
        ->execute();
});
```

Или вручную:

```php
$db = $this->get('db');
$db->beginTransaction();

try {
    // ... операции
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    throw $e;
}
```

## Сущности (Entities)

Axiom поддерживает маппинг результатов запросов на объекты-сущности. Вы можете определить класс сущности с аннотациями (PHP 8+) или использовать простой маппинг.

### Простой маппинг

```php
class UserEntity
{
    public int $id;
    public string $name;
    public string $email;
}

$users = $db->from('users')
    ->select(['id', 'name', 'email'])
    ->entity(UserEntity::class)
    ->get();

foreach ($users as $user) {
    echo $user->name;
}
```

### Аннотации (модуль `axiom/entity`)

Установите модуль:

```bash
composer require axiom/entity
```

Определите сущность:

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
    
    #[ORM\Column(name: 'email', type: 'string', length: 255)]
    private string $email = '';
    
    // геттеры и сеттеры
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }
}
```

Использование:

```php
$user = User::find(1); // Статический метод, предоставляемый трейтом
$user->setName('Новое имя');
$user->save();

$newUser = new User();
$newUser->setName('Иван');
$newUser->setEmail('ivan@example.com');
$newUser->save();
```

## Миграции базы данных

Axiom включает модуль миграций (`axiom/migration`), который позволяет управлять схемой базы данных через версионные SQL-файлы.

### Установка

```bash
composer require axiom/migration
```

### Создание миграции

```bash
php arc make:migration create_users_table
```

Файл миграции создастся в `migrations/`. Отредактируйте его:

```php
<?php

use Axiom\Migration\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->table('users')
            ->addColumn('id', 'integer', ['autoincrement' => true])
            ->addColumn('name', 'string', ['length' => 255])
            ->addColumn('email', 'string', ['length' => 255])
            ->addColumn('created_at', 'datetime')
            ->addPrimaryKey(['id'])
            ->create();
    }

    public function down(): void
    {
        $this->table('users')->drop();
    }
}
```

### Выполнение миграций

```bash
php arc db:migrate
```

### Откат миграции

```bash
php arc db:rollback
```

## Кэширование запросов

Модуль `axiom/cache` позволяет кэшировать результаты запросов.

```bash
composer require axiom/cache
```

Использование:

```php
// Кэшировать на 5 минут (300 секунд)
$users = $db->from('users')->cache(300)->get();

// С пользовательским ключом
$users = $db->from('users')->remember('popular_users', 600)->get();
```

## Отношения "многие-ко-многим"

Модуль `axiom/many-to-many` предоставляет удобные методы для работы со связующими таблицами.

```bash
composer require axiom/many-to-many
```

Пример:

```php
$user = $db->from('users')->where('id', '=', 1)->first();
$user->roles()->attach(3);           // Добавить роль с ID 3
$user->roles()->detach(3);           // Удалить роль
$user->roles()->sync([1, 2, 3]);     // Синхронизировать роли
$roles = $user->roles()->get();      // Получить все роли пользователя
```

## Советы по использованию моделей

1. **Разделяйте ответственность** — модели должны содержать бизнес-логику, связанную с данными, но не заниматься рендерингом или обработкой HTTP-запросов.

2. **Используйте трейт `ModelOrmTrait`** для удобного доступа к Axiom внутри моделей.

3. **Не забывайте про инъекцию зависимостей** — через `$this->get()` можно получить любой сервис (логгер, кэш, валидатор и т.д.).

4. **Пишите тесты** — модели легко тестировать, потому что они не зависят от контроллеров или представлений.

5. **Используйте миграции** для управления схемой базы данных — это гарантирует согласованность между окружениями.

## Пример полной модели

```php
<?php

declare(strict_types=1);

namespace app\home\modules\blog\model;

use Architect\Services\Mvc\ModelBase;
use Axiom\Orm\Integrations\Architect\ModelOrmTrait;

class Post extends ModelBase
{
    use ModelOrmTrait;

    public function getPublished(): array
    {
        return $this->db()
            ->from('posts')
            ->where('status', '=', 'published')
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    public function findWithComments(int $id): ?array
    {
        $post = $this->db()
            ->from('posts')
            ->where('id', '=', $id)
            ->first();

        if (!$post) {
            return null;
        }

        $comments = $this->db()
            ->from('comments')
            ->where('post_id', '=', $id)
            ->orderBy('created_at')
            ->get();

        $post['comments'] = $comments;
        return $post;
    }

    public function incrementViews(int $id): void
    {
        $this->db()
            ->update('posts')
            ->set(['views' => $this->raw('views + 1')])
            ->where('id', '=', $id)
            ->execute();
    }
}
```

## Заключение

Модели в Architect RED 2 — это мощный инструмент для работы с данными. Вы можете выбирать между простыми базовыми моделями и полнофункциональным ORM Axiom в зависимости от сложности проекта. Axiom предоставляет все необходимые возможности для построения запросов, транзакций, миграций и работы с сущностями, что делает разработку быстрой и безопасной.

Для дальнейшего изучения рекомендуем ознакомиться с [официальной документацией Axiom](https://github.com/axiom-orm/axiom) и разделом [База данных](database.md).