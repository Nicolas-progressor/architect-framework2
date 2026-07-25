# Модели (Model)

Модели представляют бизнес-логику приложения и обеспечивают взаимодействие с данными, обычно хранящимися в базе данных. В Architect RED 2 модели могут быть двух типов: **базовые модели** (наследующие `ModelBase`) и **модели с использованием ORM Axiom**. Компонент Model предоставляет абстракцию для работы с данными, валидации и отношений.

## Базовый класс ModelBase

`Architect\Services\Mvc\ModelBase` – абстрактный класс, предоставляющий общую функциональность для всех моделей. Он включает:

- Связь с контейнером зависимостей.
- Доступ к сервисам (logger, config, database).
- Базовые методы для CRUD-операций.

### Создание модели

```php
namespace App\Modules\User;

use Architect\Services\Mvc\ModelBase;

class UserModel extends ModelBase
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'password'];
    protected array $guarded = ['id', 'created_at'];
}
```

### Свойства модели

- `$table` – имя таблицы в БД (по умолчанию определяется по имени класса).
- `$fillable` – поля, которые можно массово назначать (mass assignment).
- `$guarded` – поля, которые нельзя массово назначать.
- `$primaryKey` – имя первичного ключа (по умолчанию `id`).
- `$timestamps` – использовать ли поля `created_at` и `updated_at` (по умолчанию `true`).

## Работа с базой данных

### Использование Axiom ORM

Architect интегрирован с Axiom ORM, который предоставляет fluent query builder. Модели могут использовать трейт `ModelOrmTrait` для доступа к ORM.

```php
use Architect\Services\Mvc\ModelBase;
use Axiom\Orm\Integrations\Architect\ModelOrmTrait;

class UserModel extends ModelBase
{
    use ModelOrmTrait;

    public function getActiveUsers()
    {
        return $this->db()
            ->from($this->table)
            ->where('status', '=', 'active')
            ->orderBy('name')
            ->get();
    }
}
```

Метод `db()` возвращает экземпляр `QueryBuilder` для текущего соединения.

### Прямое использование Axiom

Вы также можете использовать Axiom ORM напрямую, без наследования от ModelBase:

```php
use Axiom\Orm\Orm;

$users = Orm::table('users')->where('active', 1)->get();
```

## CRUD операции

### Создание записи

```php
$id = $this->create([
    'name' => 'John',
    'email' => 'john@example.com'
]);
```

Метод `create` автоматически обрабатывает `fillable`/`guarded` и возвращает ID новой записи.

### Чтение записей

```php
$user = $this->find(1); // по первичному ключу
$users = $this->where('status', 'active')->get();
$first = $this->first();
```

### Обновление записи

```php
$updated = $this->update(1, ['name' => 'Jane']);
// или
$user = $this->find(1);
$user->name = 'Jane';
$user->save();
```

### Удаление записи

```php
$this->delete(1);
// или
$user = $this->find(1);
$user->delete();
```

## Валидация данных

Модели могут использовать валидатор из сервиса `form`. Пример:

```php
public function validate(array $data)
{
    $validator = $this->form->validator($data, [
        'name' => 'required|min:3',
        'email' => 'required|email',
    ]);

    if ($validator->fails()) {
        throw new ValidationException($validator->errors());
    }
}
```

## Отношения (Relationships)

Architect не включает встроенную систему отношений (relationships), но вы можете реализовать их вручную с помощью методов модели или использовать возможности Axiom ORM (если подключён модуль `axiom/many-to-many`).

Пример отношения "один ко многим":

```php
public function posts()
{
    return $this->db()
        ->from('posts')
        ->where('user_id', '=', $this->id)
        ->get();
}
```

## События модели (Model Events)

Модели могут инициировать события на определённых этапах жизненного цикла (создание, обновление, удаление). Для этого переопределите соответствующие методы:

- `beforeCreate(array $data): array`
- `afterCreate(int $id): void`
- `beforeUpdate(int $id, array $data): array`
- `afterUpdate(int $id): void`
- `beforeDelete(int $id): bool`
- `afterDelete(int $id): void`

Пример:

```php
protected function beforeCreate(array $data): array
{
    $data['created_at'] = date('Y-m-d H:i:s');
    return $data;
}
```

## Работа с несколькими базами данных

Модель может использовать другое соединение с БД, задав свойство `$connection`:

```php
protected string $connection = 'secondary';
```

Соединение должно быть определено в конфигурации `app/config/database.json`.

## Интеграция с контроллерами

В контроллерах модель обычно внедряется через конструктор или создаётся через контейнер.

```php
class UserController extends Controller
{
    public function __construct(private UserModel $userModel)
    {}

    public function index()
    {
        $users = $this->userModel->getActiveUsers();
        return $this->view('users.index', compact('users'));
    }
}
```

## Тестирование моделей

Для тестирования моделей рекомендуется использовать тестовую базу данных (например, SQLite в памяти) и транзакции, чтобы изолировать тесты.

```php
public function test_user_creation()
{
    $model = new UserModel($this->container);
    $id = $model->create(['name' => 'Test']);
    $this->assertGreaterThan(0, $id);
}
```

## Расширение модели

Вы можете создавать базовые классы для общих функций, например `App\Core\Model`, который расширяет `ModelBase` и добавляет логирование, soft deletes или аудит.

## Примеры

### Полноценная модель пользователя

```php
namespace App\Modules\User;

use Architect\Services\Mvc\ModelBase;
use Axiom\Orm\Integrations\Architect\ModelOrmTrait;

class UserModel extends ModelBase
{
    use ModelOrmTrait;

    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'password'];
    protected array $hidden = ['password', 'remember_token'];

    public function findByEmail(string $email): ?array
    {
        return $this->db()
            ->from($this->table)
            ->where('email', '=', $email)
            ->first();
    }

    public function updateLastLogin(int $userId): bool
    {
        return $this->db()
            ->update($this->table)
            ->set(['last_login' => date('Y-m-d H:i:s')])
            ->where('id', '=', $userId)
            ->execute();
    }
}
```

## Заключение

Модели в Architect RED 2 предоставляют мощный и гибкий способ работы с данными, сочетая простоту базового класса с расширенными возможностями Axiom ORM. Правильное использование моделей способствует поддержанию чистой архитектуры и упрощает тестирование.

Дополнительные сведения см. в [документации по моделям](../docs2/models.md) и [базе данных](../docs2/database.md).