# Миграции базы данных

Миграции – это способ управлять изменениями схемы базы данных в контролируемом и версионном виде. Architect RED 2 использует модуль **Axiom Migration**, который предоставляет удобный интерфейс для создания, применения и отката миграций.

## Установка

Модуль миграций поставляется отдельно и требует установки через Composer:

```bash
composer require axiom/migration
```

После установки убедитесь, что конфигурация базы данных (`app/config/database.json`) корректна, так как миграции будут использовать указанное соединение.

## Создание миграции

Создайте новую миграцию с помощью консольной команды:

```bash
php vendor/bin/axiom make:migration create_users_table
```

Будет создан файл в папке `migrations/` с именем вида `YYYY_MM_DD_HHMMSS_create_users_table.php`.

### Структура миграции

```php
<?php

declare(strict_types=1);

use Axiom\Migration\Migration;
use Axiom\Migration\Blueprint;

class CreateUsersTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->drop('users');
    }
}
```

## Методы Blueprint

Blueprint предоставляет методы для определения столбцов и ограничений.

### Типы столбцов

| Метод | Описание |
|-------|----------|
| `id()` | Автоинкрементный первичный ключ (bigint) |
| `string($column, $length = 255)` | Строка переменной длины |
| `text($column)` | Текст большого объёма |
| `integer($column)` | Целое число |
| `bigInteger($column)` | Большое целое |
| `float($column, $total = 8, $places = 2)` | Число с плавающей точкой |
| `decimal($column, $total = 8, $places = 2)` | Десятичное число |
| `boolean($column)` | Логическое значение |
| `date($column)` | Дата (Y-m-d) |
| `datetime($column)` | Дата и время |
| `timestamp($column)` | Метка времени (TIMESTAMP) |
| `timestamps()` | Добавляет `created_at` и `updated_at` |
| `json($column)` | JSON-поле |
| `enum($column, array $allowed)` | Перечисление |

### Модификаторы

Модификаторы добавляют ограничения или свойства столбцу:

- `->unique()` – уникальный индекс
- `->nullable()` – разрешает NULL
- `->default($value)` – значение по умолчанию
- `->unsigned()` – беззнаковое целое
- `->after($column)` – разместить после указанного столбца
- `->comment($text)` – комментарий к столбцу

### Индексы и ключи

```php
$table->index('email'); // обычный индекс
$table->unique('username'); // уникальный индекс
$table->primary('id'); // первичный ключ (обычно используется id())
$table->foreign('user_id')->references('id')->on('users'); // внешний ключ
```

## Выполнение миграций

### Применение миграций

Чтобы применить все неприменённые миграции, выполните:

```bash
php vendor/bin/axiom migrate
```

Можно указать конкретное соединение:

```bash
php vendor/bin/axiom migrate --connection=mysql
```

### Откат миграций

Откатить последнюю миграцию:

```bash
php vendor/bin/axiom rollback
```

Откатить несколько миграций (например, 3):

```bash
php vendor/bin/axiom rollback --step=3
```

Откатить все миграции:

```bash
php vendor/bin/axiom reset
```

### Повторное выполнение

Откатить и снова применить миграции:

```bash
php vendor/bin/axiom refresh
```

### Просмотр статуса

Увидеть список миграций и их состояние:

```bash
php vendor/bin/axiom status
```

Вывод будет примерно таким:

```
+----+------------------------------------------------+-------+
| #  | Migration                                      | Batch |
+----+------------------------------------------------+-------+
| 1  | 2026_01_15_000001_create_users_table          | 1     |
| 2  | 2026_01_15_000002_add_age_to_users            | 1     |
| 3  | 2026_01_15_000003_create_posts_table          | 2     |
+----+------------------------------------------------+-------+
```

## Работа с данными в миграциях

Миграции могут не только изменять структуру, но и заполнять таблицы данными (сиды). Используйте методы `insert`, `update`, `delete` внутри `up()`.

```php
public function up(): void
{
    $this->table('users')->insert([
        ['name' => 'Admin', 'email' => 'admin@example.com'],
        ['name' => 'User', 'email' => 'user@example.com'],
    ]);
}
```

Для отката можно удалить добавленные данные:

```php
public function down(): void
{
    $this->table('users')->where('email', 'admin@example.com')->delete();
}
```

## Миграции в нескольких базах данных

Если у вас несколько соединений, можно указать, к какой базе данных относится миграция, задав свойство `$connection` в классе:

```php
class CreateLogsTable extends Migration
{
    protected string $connection = 'logs_db';

    public function up(): void
    {
        // ...
    }
}
```

## Интеграция с Architect

### Автоматическая загрузка миграций

При использовании Axiom ORM через `OrmServiceProvider` миграции автоматически подхватываются, если конфигурация базы данных загружена.

### Консольные команды Architect

Architect предоставляет собственные команды для миграций через `bin/arc`:

```bash
php bin/arc migrate
php bin/arc migrate:rollback
php bin/arc migrate:status
```

Эти команды являются обёртками над `axiom` и используют конфигурацию из `app/config/database.json`.

## Миграции в CI/CD

Для автоматического применения миграций при развёртывании добавьте шаг в ваш пайплайн:

```yaml
# .github/workflows/deploy.yml
- name: Run migrations
  run: php vendor/bin/axiom migrate --force
```

Флаг `--force` позволяет выполнять миграции в production без интерактивного подтверждения.

## Рекомендации

- **Именование миграций**: используйте понятные имена, отражающие суть изменения.
- **Одна миграция – одно изменение**: не объединяйте несвязанные изменения в одну миграцию.
- **Тестирование миграций**: перед применением в production протестируйте миграции на staging.
- **Резервное копирование**: перед массовыми изменениями сделайте бэкап базы данных.
- **Версионирование**: храните миграции в системе контроля версий (Git).

## Примеры

### Добавление столбца

```php
public function up(): void
{
    $this->table('users', function (Blueprint $table) {
        $table->string('phone', 20)->nullable()->after('email');
    });
}

public function down(): void
{
    $this->table('users', function (Blueprint $table) {
        $table->dropColumn('phone');
    });
}
```

### Создание таблицы с внешним ключом

```php
public function up(): void
{
    $this->create('posts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->string('title');
        $table->text('content');
        $table->timestamps();
    });
}

public function down(): void
{
    $this->drop('posts');
}
```

## Устранение проблем

### Ошибка "Table already exists"

Убедитесь, что миграция ещё не была применена. Проверьте статус и при необходимости откатите.

### Ошибка соединения

Проверьте конфигурацию базы данных и убедитесь, что сервер доступен.

### Ошибка синтаксиса SQL

Некоторые методы Blueprint могут генерировать SQL, несовместимый с вашей СУБД. Проверьте документацию Axiom Migration для вашего драйвера.

## Заключение

Миграции – мощный инструмент для управления эволюцией схемы базы данных. Используйте их, чтобы обеспечить согласованность между окружениями и упростить развёртывание.

Дополнительные сведения см. в разделах [База данных](database.md) и [Консольные команды](console.md).