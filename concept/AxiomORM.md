### Концепция ORM Architect Axiom

### Установка
Architect Axiom устанавливается через Composer:
```json
// composer.json
{
  "require": {
    "axiom": "^1.0"
  }
}
```

#### Общее описание

**ORM Architect Axiom** — универсальный ORM‑конструктор запросов для реляционных БД в PHP, совместимый с MVC‑фреймворками и работающий автономно. ORM не заменяет модель, а дополняет её: подключается через отдельные файлы и предоставляет гибкий интерфейс для построения запросов в ООП‑стиле.

#### Ключевые принципы

* **Разделение ответственности**: ORM работает отдельно от модели, не подменяет её логику.
* **Гибкость**: поддержка чистого SQL и ООП‑конструктора запросов.
* **Мульти‑БД**: абстракция для работы с MySQL, PostgreSQL, SQLite и др.
* **Конфигурируемость**: настройки в JSON‑формате.
* **Автономность**: использование в MVC‑фреймворках и standalone‑приложениях.

#### Архитектура

Система состоит из следующих компонентов:

1. **Connection Manager** — управляет подключениями к БД, читает JSON‑конфиг, создаёт PDO‑соединения.
2. **Query Builder** — ООП‑интерфейс для построения SQL‑запросов (SELECT, INSERT, UPDATE, DELETE).
3. **Adapter Layer** — драйверы для разных СУБД (MySQL, PostgreSQL, SQLite), транслирующие общие команды в специфичные для БД.
4. **Entity Mapper** — опционально преобразует строки из БД в объекты‑сущности (POO).
5. **Raw SQL Executor** — позволяет выполнять произвольные SQL‑запросы с привязкой параметров.
6. **Configuration Loader** — парсит JSON‑файл с настройками подключения и поведения.

#### Схема взаимодействия

1. Приложение загружает конфигурацию из JSON.
2. Connection Manager создаёт соединение с БД через PDO.
3. Модель импортирует нужные классы ORM.
4. Разработчик строит запрос через Query Builder или передаёт сырой SQL.
5. Adapter транслирует команды в синтаксис целевой СУБД.
6. Результат возвращается в модель (массив, объект, скаляр).

#### Формат конфигурации (JSON)

Файл `app/config/database.json`:

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
      "host": "db.example.com",
      "port": 5432,
      "database": "app_prod",
      "username": "user",
      "password": "pass",
      "schema": "public"
    }
  },
  "options": {
    "fetch_mode": "PDO::FETCH_ASSOC",
    "debug": true
  }
}
```

#### ООП‑интерфейс конструктора запросов

Пример использования в модели:

```php
<?php
// Модель UserModel.php

class UserModel extends ModelBase
{
    private $queryBuilder;

    public function __construct()
    {
        $config =ConnectionManager::getConfig('database');
        $connection = ConnectionManager::getConnection($config);
        $this->queryBuilder = new QueryBuilder($connection);
    }

    public function getActiveUsersByRole($role)
    {
        return $this->queryBuilder
            ->select(['id', 'name', 'email'])
            ->from('users')
            ->where('status', '=', 'active')
            ->andWhere('role', '=', $role)
            ->orderBy('name', 'ASC')
            ->limit(50)
            ->get();
    }

    public function updateUserEmail($userId, $newEmail)
    {
        return $this->queryBuilder
            ->update('users')
            ->set(['email' => $newEmail])
            ->where('id', '=', $userId)
            ->execute();
    }
}
?>
```

#### Поддержка чистого SQL

Метод `raw()` позволяет выполнить произвольный запрос:

```php
public function getComplexReport($startDate, $endDate)
{
    $sql = "
        SELECT u.name, COUNT(o.id) as order_count, SUM(o.total) as total_spent
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id AND o.created_at BETWEEN ? AND ?
        WHERE u.status = 'active'
        GROUP BY u.id
        HAVING total_spent > 1000
    ";
    return $this->queryBuilder->raw($sql, [$startDate, $endDate])->get();
}
```

#### Поддержка разных СУБД

Адаптерный слой обеспечивает совместимость:

* **MySQL**: использует `LIMIT`, `AUTO_INCREMENT`.
* **PostgreSQL**: использует `LIMIT`/`OFFSET`, последовательности (`SERIAL`).
* **SQLite**: поддерживает `AUTOINCREMENT`, ограниченную поддержку оконных функций.

При вызове методов Query Builder (например, `limit()`, `offset()`) адаптер транслирует их в синтаксис текущей БД.

#### Особенности реализации

* **PDO‑основа**: все запросы используют подготовленные выражения (`prepare`/`execute`) для защиты от SQL‑инъекций.
* **Ленивое выполнение**: методы `get()`, `first()`, `execute()` инициируют запрос; до этого объект накапливает условия.
* **Транзакции**: поддержка `beginTransaction()`, `commit()`, `rollBack()` через Connection Manager.
* **Кастомизация**: возможность расширить Query Builder через наследование или трейты.

#### Сценарии использования

1. **В MVC‑фреймворке**: модель импортирует Query Builder, строит запросы, возвращает данные контроллеру.
2. **Standalone‑приложение**: скрипт загружает конфигурацию, создаёт экземпляр Query Builder, выполняет запросы без фреймворка.
3. **Гибридный режим**: часть запросов через конструктор, часть — через `raw()` для сложных отчётов.

---

#### Преимущества

* **Прозрачность**: модель сохраняет контроль над логикой; ORM — инструмент, а не замена.
* **Переносимость**: смена БД требует лишь правки JSON‑конфига и проверки SQL‑специфичных участков.
* **Производительность**: отсутствие «тяжёлой» объектной обёртки; прямой доступ к PDO при необходимости.
* **Расширяемость**: легко добавить новый адаптер или метод в Query Builder.

### Полный список модификаторов ORM Architect Axiom

#### 1. Модификаторы выборки данных (SELECT)

* **`select(array $columns)`** — указывает столбцы для выборки. Пример: `->select(['id', 'name', 'email'])`.
* **`from(string $table)`** — задаёт таблицу для выборки. Пример: `->from('users')`.
* **`join(string $table, string $first, string $operator, string $second)`** — добавляет INNER JOIN. Пример: `->join('orders', 'users.id', '=', 'orders.user_id')`.
* **`leftJoin(string $table, string $first, string $operator, string $second)`** — добавляет LEFT JOIN.
* **`rightJoin(string $table, string $first, string $operator, string $second)`** — добавляет RIGHT JOIN.
* **`where(string $column, string $operator, mixed $value)`** — добавляет условие WHERE. Пример: `->where('status', '=', 'active')`.
* **`andWhere(string $column, string $operator, mixed $value)`** — добавляет дополнительное условие с оператором AND.
* **`orWhere(string $column, string $operator, mixed $value)`** — добавляет условие с оператором OR.
* **`groupBy(array|string $columns)`** — группирует результаты по указанным столбцам. Пример: `->groupBy('role')`.
* **`having(string $column, string $operator, mixed $value)`** — фильтрует сгруппированные результаты (аналог WHERE для GROUP BY). Пример: `->having('COUNT(id)', '>', 5)`.
* **`orderBy(string $column, string $direction = 'ASC')`** — сортирует результаты. Направление: `'ASC'` или `'DESC'`. Пример: `->orderBy('created_at', 'DESC')`.
* **`limit(int $value)`** — ограничивает количество возвращаемых записей. Пример: `->limit(10)`.
* **`offset(int $value)`** — задаёт смещение для пагинации. Пример: `->offset(20)`.

#### 2. Модификаторы для вставки данных (INSERT)

* **`insert(string $table)`** — начинает запрос на вставку в указанную таблицу. Пример: `->insert('users')`.
* **`set(array $data)`** — передаёт массив данных для вставки. Ключи — имена столбцов, значения — данные. Пример: `->set(['name' => 'John', 'email' => 'john@example.com'])`.

#### 3. Модификаторы для обновления данных (UPDATE)

* **`update(string $table)`** — начинает запрос на обновление в указанной таблице. Пример: `->update('users')`.
* **`set(array $data)`** — передаёт массив полей и значений для обновления. Пример: `->set(['status' => 'inactive'])`.

#### 4. Модификаторы для удаления данных (DELETE)

* **`delete(string $table = null)`** — начинает запрос на удаление. Если таблица не указана, используется таблица из `from()`. Пример: `->delete('users')` или `->from('users')->delete()`.

#### 5. Вспомогательные модификаторы

* **`raw(string $sql, array $bindings = [])`** — выполняет произвольный SQL‑запрос с привязкой параметров. Пример:
  ```php
  ->raw("SELECT * FROM users WHERE created_at > ?", [$startDate])
  ```
* **`distinct()`** — добавляет ключевое слово DISTINCT к SELECT, исключая дубликаты. Пример: `->select('role')->distinct()`.
* **`count(string $column = '*')`**, **`sum(string $column)`**, **`avg(string $column)`**, **`max(string $column)`**, **`min(string $column)`** — агрегатные функции. Возвращают соответствующий скалярный результат. Примеры:
  * `->count('id')` — количество записей;
  * `->sum('amount')` — сумма значений в столбце.
* **`table(string $table)`** — альтернативный способ указать таблицу (может использоваться вместо `from()`).

#### 6. Модификаторы транзакций

* **`beginTransaction()`** — начинает транзакцию БД.
* **`commit()`** — фиксирует транзакцию.
* **`rollBack()`** — откатывает транзакцию.

#### 7. Модификаторы обработки результатов

* **`get()`** — выполняет запрос SELECT и возвращает массив результатов (каждая строка — ассоциативный массив).
* **`first()`** — выполняет SELECT и возвращает первую строку результата (ассоциативный массив) или `null`, если строк нет.
* **`pluck(string $column)`** — возвращает массив значений из указанного столбца. Пример: `->pluck('name')` вернёт `['John', 'Jane', ...]`.
* **`execute()`** — выполняет INSERT, UPDATE или DELETE и возвращает количество затронутых строк (int).
* **`exists()`** — проверяет существование записей по условиям запроса, возвращает `true` или `false`. Пример: `->from('users')->where('email', '=', $email)->exists()`.

#### 8. Модификаторы условий с массивами и подзапросами

* **`whereIn(string $column, array $values)`** — WHERE column IN (...). Пример: `->whereIn('role', ['admin', 'moderator'])`.
* **`whereNotIn(string $column, array $values)`** — WHERE column NOT IN (...).
* **`whereNull(string $column)`** — WHERE column IS NULL.
* **`whereNotNull(string $column)`** — WHERE column IS NOT NULL.
* **`whereExists(Closure $callback)`** — WHERE EXISTS (подзапрос). Пример:
  ```php
  ->whereExists(function (QueryBuilder $query) {
      $query->select('*')->from('orders')->whereRaw('orders.user_id = users.id');
  })
  ```

#### 9. Модификаторы сырого SQL внутри условий

* **`whereRaw(string $sql, array $bindings = [])`** — добавляет сырое условие в WHERE. Пример: `->whereRaw('LENGTH(name) > 5')`.
* **`havingRaw(string $sql, array $bindings = [])`** — добавляет сырое условие в HAVING.
* **`selectRaw(string $expression, array $bindings = [])`** — добавляет сырое выражение в SELECT. Пример: `->selectRaw('COUNT(*) as user_count')`.

#### 10. Модификаторы соединения условий

* **`whereBetween(string $column, array $values)`** — WHERE column BETWEEN ... AND .... Пример: `->whereBetween('age', [18, 65])`.
* **`whereNotBetween(string $column, array $values)`** — WHERE column NOT BETWEEN ... AND ....
* **`orWhereIn(string $column, array $values)`**, **`orWhereNull(string $column)`** и т. д. — аналоги `orWhere` для специфических условий.

---

### Таблица операторов сравнения

| Оператор | Описание | Пример использования |
|--------|----------|-------------------|
| `=` | Равно | `where('status', '=', 'active')` |
| `!=` или `<>` | Не равно | `where('role', '!=', 'guest')` |
| `>` | Больше | `where('age', '>', 18)` |
| `<` | Меньше | `where('score', '<', 100)` |
| `>=` | Больше или равно | `where('price', '>=', 50)` |
| `<=` | Меньше или равно | `where('discount', '<=', 20)` |
| `LIKE` | Поиск по шаблону | `where('name', 'LIKE', '%John%')` |
| `NOT LIKE` | Отрицание поиска по шаблону | `where('email', 'NOT LIKE', '%@temp.com')` |

### Дополнительные модули для ORM Architect Axiom (подключаемые через Composer)

#### 1. Модуль Many‑to‑Many

**Название пакета:** `axiom/many-to-many`

**Назначение:** реализация отношений «многие‑ко‑многим» с автоматической генерацией промежуточных таблиц и запросов.

**Возможности:**
* автоматическая генерация JOIN‑запросов для связанных сущностей;
* поддержка кастомных промежуточных таблиц;
* методы для добавления/удаления связей между записями;
* ленивая и жадная загрузка связанных записей.

**Установка:**
```bash
composer require axiom/many-to-many
```

**API модуля:**

* **`attach($relatedId, array $pivotData = [])`** — добавляет связь между записями, опционально с данными в промежуточной таблице. Пример:
  ```php
  $user->roles()->attach(3, ['assigned_at' => now()]);
  ```
* **`detach($relatedId = null)`** — удаляет связь. Если ID не указан, удаляет все связи для текущей записи. Пример:
  ```php
  $user->roles()->detach(3);
  ```
* **`sync(array $ids)`** — синхронизирует связи: оставляет только указанные ID, остальные удаляет. Пример:
  ```php
  $user->tags()->sync([1, 2, 5]);
  ```
* **`withPivot(array $columns)`** — указывает дополнительные столбцы из промежуточной таблицы, которые нужно загрузить. Пример:
  ```php
  $users = User::with('roles')->withPivot(['assigned_at'])->get();
  ```
* **`using($pivotTable)`** — задаёт кастомную промежуточную таблицу. Пример:
  ```php
  $this->belongsToMany(User::class)->using('custom_user_role');
  ```

**Пример использования:**
```php
// В модели User.php
public function roles()
{
    return $this->belongsToMany(Role::class)
        ->using('user_role')
        ->withPivot(['created_at']);
}

// Использование
$user = User::find(1);
$user->roles()->attach(2); // Добавляем роль с ID=2
$roles = $user->roles()->get(); // Получаем все роли пользователя
```

---

#### 2. Модуль Cache

**Название пакета:** `axiom/cache`

**Назначение:** кэширование результатов запросов для снижения нагрузки на БД.

**Поддерживаемые бэкенды:**
* Redis;
* Memcached;
* файловое кэширование;
* APC(u).

**Установка:**
```bash
composer require axiom/cache
```

**Конфигурация (добавляется в `config/database.json`):**
```json
"cache": {
  "driver": "redis",
  "ttl": 3600,
  "prefix": "axiom_",
  "redis": {
    "host": "127.0.0.1",
    "port": 6379
  }
}
```

**API модуля:**

* **`cache($ttl = null)`** — включает кэширование для текущего запроса с указанием времени жизни (в секундах). Если TTL не задан, используется значение по умолчанию из конфигурации. Пример:
  ```php
  $data = $query->cache(300)->get(); // Кэшируем на 5 минут
  ```
* **`remember($key, $ttl = null)`** — кэширует запрос с пользовательским ключом. Пример:
  ```php
  $data = $query->remember('popular_products', 3600)->get();
  ```
* **`flush()`** — очищает кэш для всех запросов текущего типа или по шаблону. Пример:
  ```php
  QueryBuilder::flush(); // Очищаем весь кэш
  QueryBuilder::flush('users_*'); // Очищаем кэш по шаблону
  ```
* **`disableCache()`** — временно отключает кэширование для конкретного запроса. Пример:
  ```php
  $freshData = $query->disableCache()->get();
  ```

**Пример использования:**
```php
$users = QueryBuilder::table('users')
    ->where('active', 1)
    ->cache(600) // Кэшируем результат на 10 минут
    ->get();
```

---

#### 3. Модуль Migration

**Название пакета:** `axiom/migration`

**Назначение:** управление схемой БД через миграции (создание, изменение, удаление таблиц и индексов).

**Установка:**
```bash
composer require axiom/migration
```

**Команды CLI (через встроенный консольный инструмент):**
* `axiom:migrate` — применяет все ожидающие миграции;
* `axiom:rollback` — откатывает последнюю миграцию;
* `axiom:status` — показывает статус миграций (применённые/ожидающие);
* `axiom:make:migration <name>` — создаёт новый файл миграции.

**Структура файла миграции:**
```php
<?php

use Axiom\Migration\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('created_at');
        });
    }

    public function down()
    {
        $this->drop('users');
    }
}
```

**API модуля (в миграциях):**

* **`create(string $table, Closure $callback)`** — создаёт таблицу с колонками, определёнными в замыкании.
* **`drop(string $table)`** — удаляет таблицу.
* **`table(string $table, Closure $callback)`** — изменяет существующую таблицу.
* **`rename(string $from, string $to)`** — переименовывает таблицу.
* **Методы для работы с колонками:**
  * `$table->id()` — первичный ключ типа BIGINT AUTO_INCREMENT;
  * `$table->string($column, $length = 255)` — VARCHAR;
  * `$table->integer($column)` — INT;
  * `$table->timestamp($column)` — TIMESTAMP;
  * `$table->boolean($column)` — BOOLEAN;
  * `$table->foreign($column)` — внешний ключ;
  * `$table->unique($column)` — уникальный индекс;
  * `$table->index($column)` — обычный индекс.

**Пример миграции для Many‑to‑Many:**
```php
public function up()
{
    $this->create('user_role', function (Blueprint $table) {
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('role_id');
        $table->primary(['user_id', 'role_id']);
        $table->foreign('user_id')->references('id')->on('users');
        $table->foreign('role_id')->references('id')->on('roles');
    });
}
```

---

### Интеграция модулей в основной ORM

Все модули расширяют базовый `QueryBuilder`, добавляя свои методы. Для подключения достаточно:
1. Установить пакет через Composer.
2. Настроить конфигурацию (если требуется).
3. Использовать новые методы в моделях.

**Пример комплексной работы:**
```php
$user = User::find(1)
    ->with('roles') // Many‑to‑Many
    ->cache(3600)   // Кэширование
    ->first();      // Выполнение запроса
```

Модули независимы — можно подключать только нужные. Каждый имеет свою документацию и набор тестов.

### Концепция использования Entity‑файлов в ORM Architect Axiom

#### Суть подхода

**Entity‑файлы** (сущности) — это PHP‑классы, описывающие структуру таблиц БД в объектно‑ориентированном стиле. Они позволяют:
* явно задать соответствие полей таблицы свойствам класса;
* добавить бизнес‑логику и валидацию данных;
* упростить работу с отношениями между таблицами;
* обеспечить типобезопасность при работе с данными.

#### Архитектура интеграции

Для поддержки Entity‑файлов в ORM Architect Axiom вводится новый компонент:

**Entity Manager** — сервис, отвечающий за:
* загрузку и кэширование метаданных сущностей;
* преобразование строк из БД в объекты‑сущности;
* отслеживание изменений в объектах (dirty checking);
* каскадное сохранение связанных сущностей.

#### Формат Entity‑файла

Entity‑файл — это обычный PHP‑класс с аннотациями (или атрибутами PHP 8+), описывающими соответствие полей таблицы и свойств класса.

**Пример Entity для таблицы `users`:**

```php
<?php
// Entity/User.php

use Axiom\ORM\Annotations as ORM;

#[ORM\Entity(table: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'email', type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(name: 'status', type: 'string')]
    private string $status = 'active';

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTime $createdAt;

    // Геттеры и сеттеры
    public function getId(): int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function getCreatedAt(): \DateTime { return $this->createdAt; }
    public function setCreatedAt(\DateTime $createdAt): void { $this->createdAt = $createdAt; }
}
```

#### Новые модификаторы Query Builder для работы с Entity

В `QueryBuilder` добавляются методы:

* **`entity(string $entityClass)`** — указывает класс сущности, в который нужно преобразовать результат запроса. Пример:
  ```php
  ->entity(User::class)
  ```
* **`with(string $relation)`** — загружает связанные сущности (ленивая загрузка). Пример:
  ```php
  ->with('roles')
  ```
* **`save(object $entity)`** — сохраняет сущность в БД (INSERT или UPDATE в зависимости от наличия ID). Пример:
  ```php
  $user = new User();
  $user->setName('John');
  $user->setEmail('john@example.com');
  $this->queryBuilder->save($user);
  ```
* **`deleteEntity(object $entity)`** — удаляет сущность из БД. Пример:
  ```php
  $this->queryBuilder->deleteEntity($user);
  ```

#### Схема взаимодействия с Entity

1. Разработчик создаёт Entity‑файлы для таблиц БД.
2. `EntityManager` загружает метаданные сущностей (кэширует их для производительности).
3. В модели используется `QueryBuilder` с указанием сущности через `entity()`.
4. ORM выполняет запрос, получает данные из БД и преобразует их в объекты‑сущности.
5. Разработчик работает с объектами (вызывает методы, изменяет свойства).
6. Изменения сохраняются через `save()` или `deleteEntity()`.

#### Пример использования в модели

```php
<?php
// Модель UserModel.php

class UserModel extends ModelBase
{
    private $queryBuilder;

    public function __construct()
    {
        $config = ConnectionManager::getConfig('database');
        $connection = ConnectionManager::getConnection($config);
        $this->queryBuilder = new QueryBuilder($connection);
    }

    public function findActiveUsers(): array
    {
        return $this->queryBuilder
            ->select(['id', 'name', 'email', 'status', 'created_at'])
            ->from('users')
            ->where('status', '=', 'active')
            ->orderBy('created_at', 'DESC')
            ->entity(User::class) // Преобразуем в объекты User
            ->get();
    }

    public function createUser(string $name, string $email): User
    {
        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setCreatedAt(new \DateTime());

        $this->queryBuilder->save($user); // Сохраняем в БД
        return $user;
    }
}
?>
```

#### Поддержка отношений между сущностями

Для описания связей между таблицами используются специальные аннотации:

* `#[ORM\OneToMany]` — отношение «один‑ко‑многим»;
* `#[ORM\ManyToOne]` — отношение «многие‑к‑одному»;
* `#[ORM\ManyToMany]` — отношение «многие‑ко‑многим».

**Пример связи «один‑ко‑многим» (User → Order):**

```php
#[ORM\Entity(table: 'users')]
class User
{
    // ... поля ...

    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'user')]
    private Collection $orders;

    public function getOrders(): Collection
    {
        return $this->orders;
    }
}

#[ORM\Entity(table: 'orders')]
class Order
{
    // ... поля ...

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private User $user;

    public function getUser(): User
    {
        return $this->user;
    }
}
```

#### Особенности реализации

* **Ленивая загрузка** (`Lazy Loading`): связанные сущности загружаются только при обращении к ним (через прокси‑объекты).
* **Каскадное сохранение** (`Cascade Persist`): при сохранении сущности автоматически сохраняются связанные объекты.
* **Отслеживание изменений** (`Dirty Checking`): ORM отслеживает изменённые поля и обновляет только их.
* **Кэширование метаданных**: описание сущностей загружается один раз и кэшируется для повышения производительности.
* **Поддержка валидации**: можно добавить аннотации для проверки данных (например, `#[Assert\NotBlank]`).

#### Сценарии использования

1. **Простая выборка**: загрузка списка сущностей с фильтрацией и сортировкой.
2. **Создание и обновление**: работа с объектами вместо массивов данных.
3. **Работа с отношениями**: загрузка связанных сущностей через `with()`.
4. **Валидация данных**: проверка корректности значений перед сохранением.
5. **Каскадные операции**: автоматическое сохранение связанных объектов.

---

#### Преимущества Entity‑подхода

* **Типобезопасность**: работа с объектами вместо ассоциативных массивов.
* **Бизнес‑логика в сущности**: методы для изменения состояния объекта (например, `$user->deactivate()`).
* **Упрощение рефакторинга**: изменения в структуре БД отражаются в Entity‑файлах.
* **Автоматизация**: ORM берёт на себя преобразование данных и отслеживание изменений.
* **Интеграция с модулями**: поддержка кэширования (`axiom/cache`), миграций (`axiom/migration`) и отношений (`axiom/many-to-many`).

#### Ограничения и рекомендации

* Entity‑файлы следует размещать в отдельной директории (например, `App/Entity/`).
* Для сложных запросов можно комбинировать Entity с `raw()` (загрузка данных без преобразования в объекты).
* При массовом обновлении рекомендуется использовать `QueryBuilder::update()` вместо сохранения каждой сущности отдельно.

### Концепция использования JSON ORM‑файлов в ORM Architect Axiom

#### Суть подхода

**JSON ORM‑файлы** — это конфигурационные файлы в формате JSON, описывающие структуру таблиц БД, связи между ними и правила маппинга данных. Они служат альтернативой Entity‑файлам (PHP‑классам с аннотациями) и позволяют:

* декларативно описывать структуру БД без написания PHP‑кода;
* быстро настраивать маппинг для существующих таблиц;
* динамически загружать схемы из внешних источников (например, API);
* упрощать миграцию между разными версиями схемы БД;
* обеспечивать единообразное описание структуры для разных языков программирования.

#### Формат JSON ORM‑файла

JSON ORM‑файл имеет следующую структуру:

```json
{
  "entities": {
    "User": {
      "table": "users",
      "fields": {
        "id": {
          "column": "id",
          "type": "integer",
          "primary": true
        },
        "name": {
          "column": "name",
          "type": "string",
          "length": 255
        },
        "email": {
          "column": "email",
          "type": "string",
          "length": 255,
          "unique": true
        },
        "status": {
          "column": "status",
          "type": "string",
          "default": "active"
        },
        "createdAt": {
          "column": "created_at",
          "type": "datetime"
        }
      },
      "relations": {
        "orders": {
          "type": "oneToMany",
          "target": "Order",
          "mappedBy": "user"
        }
      }
    },
    "Order": {
      "table": "orders",
      "fields": {
        "id": {
          "column": "id",
          "type": "integer",
          "primary": true
        },
        "userId": {
          "column": "user_id",
          "type": "integer"
        },
        "total": {
          "column": "total",
          "type": "decimal",
          "precision": 10,
          "scale": 2
        },
        "createdAt": {
          "column": "created_at",
          "type": "datetime"
        }
      },
      "relations": {
        "user": {
          "type": "manyToOne",
          "target": "User",
          "inversedBy": "orders",
          "joinColumn": "user_id"
        }
      }
    }
  }
}
```

#### Архитектура интеграции

Для поддержки JSON ORM‑файлов вводится новый компонент:

**Schema Loader** — сервис, отвечающий за:
* загрузку JSON ORM‑файлов из указанной директории;
* валидацию схемы против JSON‑схемы (опционально);
* кэширование загруженных схем для повышения производительности;
* преобразование JSON‑описания в внутренние метаданные ORM.

#### Новые модификаторы Query Builder для работы с JSON ORM

В `QueryBuilder` добавляются методы:

* **`useSchema(string $schemaPath)`** — загружает схему из JSON‑файла и применяет её к текущему запросу. Пример:
  ```php
  ->useSchema('orm/schema.json')
  ```
* **`mapTo(string $entityName)`** — указывает сущность из схемы, в которую нужно преобразовать результат запроса. Пример:
  ```php
  ->mapTo('User')
  ```
* **`saveFromArray(string $entityName, array $data)`** — сохраняет данные в БД, используя правила маппинга из схемы. Пример:
  ```php
  $this->queryBuilder
      ->useSchema('orm/schema.json')
      ->saveFromArray('User', [
          'name' => 'John',
          'email' => 'john@example.com'
      ]);
  ```
* **`deleteByCriteria(string $entityName, array $criteria)`** — удаляет записи по условиям, используя маппинг схемы. Пример:
  ```php
  $this->queryBuilder
      ->useSchema('orm/schema.json')
      ->deleteByCriteria('User', ['status' => 'inactive']);
  ```

#### Схема взаимодействия с JSON ORM

1. Разработчик создаёт JSON ORM‑файлы для таблиц БД (например, в директории `app/orm/`).
2. `Schema Loader` загружает и валидирует схемы (кэширует их для производительности).
3. В модели используется `QueryBuilder` с указанием схемы через `useSchema()`.
4. ORM выполняет запрос, получает данные из БД и преобразует их в массивы или объекты согласно схеме.
5. Разработчик работает с данными (изменяет массивы, добавляет новые поля).
6. Изменения сохраняются через `saveFromArray()` или `deleteByCriteria()`.

#### Пример использования в модели

```php
<?php
// Модель UserModel.php

class UserModel extends ModelBase
{
    private $queryBuilder;

    public function __construct()
    {
        $config = ConnectionManager::getConfig('database');
        $connection = ConnectionManager::getConnection($config);
        $this->queryBuilder = new QueryBuilder($connection);
    }

    public function findActiveUsers(): array
    {
        return $this->queryBuilder
            ->select(['id', 'name', 'email', 'status', 'created_at'])
            ->from('users')
            ->where('status', '=', 'active')
            ->orderBy('created_at', 'DESC')
            ->useSchema('orm/schema.json') // Загружаем схему
            ->mapTo('User') // Преобразуем в формат сущности User
            ->get();
    }

    public function createUser(string $name, string $email): array
    {
        $data = [
            'name' => $name,
            'email' => $email,
            'createdAt' => (new \DateTime())->format('Y-m-d H:i:s')
        ];

        return $this->queryBuilder
            ->useSchema('orm/schema.json')
            ->saveFromArray('User', $data); // Сохраняем по схеме
    }
}
?>
```

#### Поддержка отношений между сущностями в JSON

Связи между таблицами описываются в разделе `relations` JSON‑файла:

* `"type": "oneToMany"` — отношение «один‑ко‑многим»;
* `"type": "manyToOne"` — отношение «многие‑к‑одному»;
* `"type": "manyToMany"` — отношение «многие‑ко‑многим».

**Пример связи «многие‑ко‑многим» (User ↔ Role):**

```json
"User": {
  // ... поля ...
  "relations": {
    "roles": {
      "type": "manyToMany",
      "target": "Role",
      "pivotTable": "user_role",
      "localKey": "user_id",
      "foreignKey": "role_id"
    }
  }
},
"Role": {
  // ... поля ...
  "relations": {
    "users": {
      "type": "manyToMany",
      "target": "User",
      "pivotTable": "user_role",
      "localKey": "role_id",
      "foreignKey": "user_id"
    }
  }
}
```

#### Особенности реализации

* **Динамическая загрузка схем**: JSON ORM‑файлы можно загружать по требованию или при старте приложения.
* **Валидация данных**: схема может содержать правила валидации (например, `"required": true`, `"minLength": 3`).
* **Преобразование типов**: ORM автоматически преобразует типы данных из БД в PHP‑типы (например, `datetime` → `\DateTime`).
* **Поддержка миграций**: JSON‑схема может использоваться для генерации миграций через модуль `axiom/migration`.
* **Кэширование метаданных**: загруженные схемы кэшируются в бинарном формате (например, PHP‑массивы) для ускорения доступа.
* **Расширяемость**: можно добавлять кастомные типы полей и правила маппинга.

#### Сценарии использования

1. **Быстрая настройка**: описание структуры БД без написания PHP‑классов.
2. **Динамические схемы**: загрузка JSON‑файлов из внешних источников (API, микросервисы).
3. **Миграция данных**: использование JSON‑схем для конвертации данных между разными БД.
4. **Интеграция с API**: описание DTO (Data Transfer Objects) в JSON для сериализации/десериализации.
5. **Тестирование**: создание тестовых схем без изменения кода приложения.

---

#### Преимущества JSON ORM‑подхода

* **Простота**: описание структуры в декларативном формате без программирования.
* **Гибкость**: возможность изменять схему без перекомпиляции кода — достаточно отредактировать JSON‑файл.
* **Универсальность**: один и тот же JSON‑файл может использоваться разными приложениями и языками программирования.
* **Версионирование**: легко хранить разные версии схемы в системе контроля версий (Git) и отслеживать изменения.
* **Автогенерация документации**: на основе JSON‑схемы можно автоматически создавать документацию по API или структуре БД.
* **Интеграция с CI/CD**: JSON‑файлы можно валидировать на этапе сборки, предотвращая ошибки маппинга.
* **Динамическая конфигурация**: возможность загружать схемы из внешних источников (например, микросервисов или API‑шлюзов).

#### Ограничения и способы их преодоления


| Ограничение | Способ преодоления |
|----------|-----------------|
| Отсутствие строгой типизации на уровне PHP | Использование валидаторов схемы и генерации PHP‑классов из JSON (опционально) |
| Сложность описания сложной бизнес‑логики | Комбинирование с Entity‑классами: JSON для структуры, PHP‑классы для логики |
| Риск ошибок в синтаксисе JSON | Валидация схемы при загрузке (с использованием JSON Schema) |
| Снижение производительности из‑за парсинга JSON | Кэширование скомпилированных метаданных в бинарном формате (например, сериализованный массив PHP) |
| Ограниченная поддержка IDE (автодополнение, навигация) | Генерация заглушек классов (stubs) для IDE на основе JSON‑схемы |

#### Интеграция с существующими модулями ORM Architect Axiom

JSON ORM‑файлы полностью совместимы с модулями экосистемы:

1. **axiom/many-to-many**:
   * Связи `manyToMany` в JSON‑схеме автоматически преобразуются в запросы к промежуточным таблицам.
   * Методы `attach()`, `detach()`, `sync()` работают с данными, загруженными через JSON‑схему.

2. **axiom/cache**:
   * Кэширование результатов запросов, использующих JSON‑схему, работает прозрачно.
   * Ключи кэша могут включать версию схемы для автоматического инвалидирования при изменениях.

3. **axiom/migration**:
   * JSON‑схема может служить источником для генерации миграций.
   * Команда `axiom:make:migration --from-schema` создаёт шаблон миграции на основе текущей схемы.

#### Пример генерации миграции из JSON ORM

```php
// Команда CLI
php vendor/bin/axiom axiom:make:migration CreateUsersTable --from-schema=orm/schema.json

// Сгенерированный файл миграции
class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('status')->default('active');
            $table->timestamp('created_at');
        });
    }
}
```

#### Режимы работы с JSON ORM

1. **Статический режим**:
   * Схема загружается один раз при старте приложения.
   * Подходит для продакшн‑среды, где структура БД стабильна.

2. **Динамический режим**:
   * Схема перезагружается перед каждым запросом (или по таймеру).
   * Полезен в разработке для быстрого тестирования изменений.

3. **Гибридный режим**:
   * Основная схема загружается статически, отдельные сущности — динамически.
   * Используется в мультитенантных приложениях, где у каждого клиента своя структура данных.

#### Конфигурация загрузки схем

В `app/config/database.json` добавляется раздел:


```json
"orm": {
  "schema_path": "app/orm",
  "cache_enabled": true,
  "cache_driver": "file",
  "auto_reload": false,
  "validation": {
    "enabled": true,
    "schema_file": "vendor/axiom/orm/json-schema/v1.0.json"
  }
}
```

#### Инструменты для работы с JSON ORM

1. **Генератор схем**:
   * Команда `axiom:generate:schema` создаёт JSON‑файл на основе существующей БД.
   * Поддерживает фильтрацию таблиц и полей.

2. **Валидатор схем**:
   * Проверяет JSON‑файлы на соответствие спецификации ORM Architect Axiom.
   * Выводит подробные отчёты об ошибках.

3. **Компилятор схем**:
   * Преобразует JSON‑файлы в оптимизированный бинарный формат для кэширования.
   * Ускоряет загрузку метаданных в 5–10 раз.

4. **Мигратор схем**:
   * Анализирует различия между текущей БД и JSON‑схемой.
   * Генерирует SQL‑скрипты для синхронизации.

#### Рекомендации по использованию

1. Для новых проектов:
   * Начинать с JSON ORM для быстрого прототипирования.
   * Переходить на Entity‑классы по мере стабилизации схемы и добавления бизнес‑логики.

2. Для существующих приложений:
   * Использовать JSON ORM для интеграции с унаследованными таблицами.
   * Применять гибридный подход: Entity для ключевых сущностей, JSON — для вспомогательных.


3. В высоконагруженных системах:
   * Включать кэширование скомпилированных схем.
   * Минимизировать использование динамического режима.

4. При командной разработке:
   * Добавлять JSON‑схемы в репозиторий вместе с кодом.
   * Настраивать pre‑commit‑хуки для валидации схем.

---

#### Перспективы развития

1. Поддержка JSON Schema Draft 2020‑12 для строгой валидации.
2. Интеграция с OpenAPI для автоматической генерации REST API на основе схемы.
3. Визуальный редактор JSON ORM с drag‑and‑drop интерфейсом.
4. Экспорт схемы в UML‑диаграммы и документацию (Swagger/OpenAPI).
5. Поддержка GraphQL SDL для генерации GraphQL‑серверов.


#### Важные моменты
Система должна работать с другими системами Architect Framework если работает из-под неё должна быть связь с Консолью, Сервисом Debug (вывод в Debug панель), Сервисом Logger для логов, с системой Environment System для получения конфигурации для окружения.