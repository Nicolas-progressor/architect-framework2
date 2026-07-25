# Axiom ORM - Entity Support

Entity support with PHP 8+ attributes for Axiom ORM.

## Installation

```bash
composer require axiom/entity
```

## Quick Start

### 1. Define Entity

```php
<?php

use Axiom\Entity\Annotation as ORM;

#[ORM\Entity(table: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer', autoIncrement: true)]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name = '';

    #[ORM\Column(name: 'email', type: 'string', length: 255, unique: true)]
    private string $email = '';

    #[ORM\Column(name: 'status', type: 'string', default: 'active')]
    private string $status = 'active';

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTime $createdAt = null;

    // ========== Getters and Setters ==========

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function getCreatedAt(): ?\DateTime { return $this->createdAt; }
    public function setCreatedAt(?\DateTime $createdAt): void { $this->createdAt = $createdAt; }
}
```

### 2. Use Entity

```php
use App\Entity\User;

// Create
$user = new User();
$user->setName('John Doe');
$user->setEmail('john@example.com');
$user->save();

// Find
$user = User::find(1);

// Update
$user->setName('Updated Name');
$user->save();

// Delete
$user->delete();

// Query
$users = User::all();
$admins = User::findBy(['role' => 'admin']);
$user = User::findOneBy(['email' => 'john@example.com']);
```

## Available Annotations

### @Entity

Marks a class as an ORM entity.

```php
#[ORM\Entity(table: 'users', connection: 'default')]
class User { }
```

### @Column

Maps a property to a database column.

```php
#[ORM\Column(
    name: 'user_name',           // Column name in DB
    type: 'string',              // Data type
    length: 255,                 // For string types
    precision: 10, scale: 2,     // For decimal
    nullable: false,             // Allow NULL
    default: 'value',           // Default value
    unique: false,              // Unique constraint
    autoIncrement: true,        // Auto-increment
    comment: 'User name'        // Column comment
)]
private string $name = '';
```

### @Id

Marks a property as primary key.

```php
#[ORM\Id]
#[ORM\Column(name: 'id', type: 'integer')]
private ?int $id = null;
```

### @Transient

Excludes a property from persistence.

```php
#[ORM\Transient]
private string $computed = '';  // Not saved to database
```

### @OneToMany

One-to-many relationship.

```php
#[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'user', cascade: true)]
private array $orders = [];
```

### @ManyToOne

Many-to-one relationship.

```php
#[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'orders', joinColumn: 'user_id')]
private ?User $user = null;
```

### @ManyToMany

Many-to-many relationship.

```php
#[ORM\ManyToMany(
    targetEntity: Role::class,
    pivotTable: 'user_roles',
    localKey: 'user_id',
    foreignKey: 'role_id',
    cascade: true
)]
private array $roles = [];
```

## Repository API

### Basic Operations

```php
// Get repository
$repo = User::repository();

// Find all
$users = $repo->findAll();

// Find by ID
$user = $repo->find(1);

// Find by criteria
$admins = $repo->findBy(['status' => 'active']);

// Find one by criteria
$user = $repo->findOneBy(['email' => 'john@example.com']);

// Count
$count = $repo->count();

// Check exists
$exists = $repo->exists(1);

// Create new instance
$user = $repo->create(['name' => 'John', 'email' => 'john@example.com']);

// Save entity
$repo->save($user);

// Delete entity
$repo->delete($user);

// Delete by ID
$repo->deleteById(1);
```

### Pagination

```php
$page = $repo->paginate(page: 1, perPage: 15);

// $page = [
//     'data' => [...],
//     'total' => 100,
//     'page' => 1,
//     'per_page' => 15,
//     'last_page' => 7
// ]

foreach ($page['data'] as $user) {
    echo $user->getName();
}
```

### Custom Repository

```php
class UserRepository extends \Axiom\Entity\Repository
{
    public function findActive(): array
    {
        return $this->findBy(['status' => 'active']);
    }

    public function findByRole(string $role): array
    {
        return $this->findBy(['role' => $role]);
    }

    public function search(string $query): array
    {
        return $this->query()
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->get();
    }
}

// Use custom repository
$repo = new UserRepository(User::class);
$admins = $repo->findByRole('admin');
```

## Using with Query Builder

```php
use Axiom\Orm\Orm;

// Get users as entities
$users = Orm::table('users')
    ->select(['id', 'name', 'email'])
    ->where('status', '=', 'active')
    ->entity(User::class)
    ->get();

foreach ($users as $user) {
    echo $user->getName();
}

// First entity
$user = Orm::table('users')
    ->where('id', '=', 1)
    ->entity(User::class)
    ->first();
```

## Entity Metadata

```php
use Axiom\Entity\EntityManager;

$metadata = EntityManager::getMetadata(User::class);

// Get table name
$table = $metadata->table;  // 'users'

// Get primary key
$pk = $metadata->getPrimaryKey();  // 'id'

// Get columns
foreach ($metadata->columns as $column) {
    echo $column->column;   // Column name
    echo $column->type;     // Data type
    echo $column->nullable; // Is nullable
}

// Get relations
foreach ($metadata->relations as $name => $relation) {
    echo $relation->type;        // oneToMany, manyToOne, manyToMany
    echo $relation->targetEntity; // Related entity class
}
```

## Business Logic in Entity

```php
#[ORM\Entity(table: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'status', type: 'string')]
    private string $status = 'active';

    // Business methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function activate(): void
    {
        $this->status = 'active';
    }

    public function deactivate(): void
    {
        $this->status = 'inactive';
    }

    public function toggleStatus(): void
    {
        $this->status = $this->isActive() ? 'inactive' : 'active';
    }
}

// Usage
$user = User::find(1);
if ($user->isActive()) {
    $user->deactivate();
    $user->save();
}
```

## Type Conversion

The Entity Manager automatically converts between PHP and database types:

| Database Type | PHP Type |
|---------------|----------|
| `integer`, `int` | `int` |
| `float`, `double` | `float` |
| `boolean`, `bool` | `bool` |
| `datetime`, `date`, `timestamp` | `\DateTime` |
| `json` | `array` |

## Events and Hooks

For advanced use cases, extend the Repository:

```php
class UserRepository extends \Axiom\Entity\Repository
{
    public function save(object $entity): int|string
    {
        // Before save
        $this->beforeSave($entity);
        
        $result = parent::save($entity);
        
        // After save
        $this->afterSave($entity);
        
        return $result;
    }

    protected function beforeSave(User $user): void
    {
        if ($user->getCreatedAt() === null) {
            $user->setCreatedAt(new \DateTime());
        }
        $user->setUpdatedAt(new \DateTime());
    }

    protected function afterSave(User $user): void
    {
        // Clear cache, log, etc.
    }
}
```

## License

MIT
