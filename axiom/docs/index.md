# Axiom ORM - Complete Documentation

## Table of Contents

1. [Installation](#installation)
2. [Core Module](#core-module-axiomorm)
3. [Modules](#modules)
   - [Migration](#1-migration-axiommigration)
   - [Cache](#2-cache-axiomcache)
   - [Many-to-Many](#3-many-to-many-axiommany-to-many)
   - [Entity](#4-entity-axiomentity)
4. [Configuration](#configuration)
5. [Architect Framework Integration](#architect-framework-integration)

---

## Installation

### Core Package

```bash
composer require axiom/orm
```

### All Modules

```bash
composer require axiom/orm axiom/migration axiom/cache axiom/many-to-many axiom/entity
```

---

## Core Module (axiom/orm)

### Quick Start

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

### Configuration

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

### Query Builder Examples

#### SELECT

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

// Aggregates
$count = Orm::table('users')->count();
$sum = Orm::table('orders')->sum('total');
```

#### INSERT

```php
$id = Orm::table('users')
    ->insert()
    ->set([
        'name' => 'John',
        'email' => 'john@example.com',
        'created_at' => date('Y-m-d H:i:s')
    ])
    ->execute();
```

#### UPDATE

```php
$affected = Orm::table('users')
    ->update()
    ->set(['status' => 'inactive'])
    ->where('id', '=', 1)
    ->execute();
```

#### DELETE

```php
$deleted = Orm::table('users')
    ->delete()
    ->where('status', '=', 'banned')
    ->execute();
```

#### Transactions

```php
Orm::transaction(function () {
    Orm::table('accounts')
        ->update()
        ->set(['balance' => 100])
        ->where('id', '=', 1)
        ->execute();
    
    Orm::table('accounts')
        ->update()
        ->set(['balance' => 50])
        ->where('id', '=', 2)
        ->execute();
});
```

---

## Modules

### 1. Migration (`axiom/migration`)

Database migrations management.

#### Installation

```bash
composer require axiom/migration
```

#### CLI Commands

```bash
# Run all pending migrations
php vendor/bin/axiom migrate

# Rollback last migration
php vendor/bin/axiom rollback

# Reset all migrations
php vendor/bin/axiom reset

# Show migration status
php vendor/bin/axiom status

# Create new migration
php vendor/bin/axiom make:migration create_orders_table
```

#### Usage in PHP

```php
use Axiom\Migration\MigrationManager;

$manager = new MigrationManager(__DIR__ . '/migrations');
$manager->migrate();
```

#### Create Migration

```php
<?php

use Axiom\Migration\Migration;
use Axiom\Migration\Blueprint;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->drop('users');
    }
}
```

#### Blueprint API

```php
// Column types
$table->id();
$table->string('name', 255);
$table->text('description');
$table->integer('count');
$table->decimal('price', 10, 2);
$table->boolean('active');
$table->date('birthday');
$table->dateTime('created_at');
$table->json('options');
$table->uuid('id');

// Modifiers
$table->string('name')->nullable();
$table->string('name')->default('John');
$table->string('email')->unique();
$table->integer('count')->unsigned();

// Indexes
$table->index('email');
$table->unique(['email', 'tenant_id']);

// Foreign keys
$table->bigInteger('user_id')
    ->foreign('user_id')
    ->references('id')
    ->on('users')
    ->onDelete('CASCADE');
```

---

### 2. Cache (`axiom/cache`)

Query caching with multiple backend support.

#### Installation

```bash
composer require axiom/cache
```

#### Configuration

```json
{
    "cache": {
        "driver": "redis",
        "enabled": true,
        "ttl": 3600,
        "prefix": "axiom_",
        "redis": {
            "host": "127.0.0.1",
            "port": 6379
        }
    }
}
```

#### Supported Drivers

| Driver | Description |
|--------|-------------|
| `array` | In-memory array (default) |
| `apcu` | APCu user cache |
| `redis` | Redis |
| `memcached` | Memcached |
| `file` | File system cache |

#### Usage

```php
use Axiom\Orm\Orm;
use Axiom\Cache\Cache;

// Cache query results
$users = Orm::table('users')
    ->where('status', '=', 'active')
    ->cache(300)  // 5 minutes
    ->get();

// Custom cache key
$users = Orm::table('users')
    ->where('status', '=', 'active')
    ->remember('popular_users', 600)
    ->get();

// Direct cache usage
Cache::set('my_key', $data, 300);
$data = Cache::get('my_key');
Cache::forget('my_key');
Cache::flush();
```

---

### 3. Many-to-Many (`axiom/many-to-many`)

Many-to-many relationships support.

#### Installation

```bash
composer require axiom/many-to-many
```

#### Define Relationship

```php
<?php

use Axiom\ManyToMany\BelongsToManyTrait;

class User
{
    use BelongsToManyTrait;
    
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }
}
```

#### Usage

```php
// Attach roles
$user->roles()->attach(3);
$user->roles()->attach([1, 2, 3]);

// Detach roles
$user->roles()->detach(3);
$user->roles()->detach();  // All

// Sync (keep only these)
$user->roles()->sync([1, 2, 3]);

// Toggle
$user->roles()->toggle([1, 2]);

// Get roles
$roles = $user->roles()->get();

// Check if has role
if ($user->roles()->has(3)) {
    // Has role
}

// Update pivot data
$user->roles()->updatePivot(3, ['assigned_at' => now()]);
```

---

### 4. Entity (`axiom/entity`)

Entity support with PHP 8+ attributes.

#### Installation

```bash
composer require axiom/entity
```

#### Define Entity

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

    // Relationships
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'user')]
    private array $orders = [];

    // ========== Getters and Setters ==========

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): void { $this->id = $id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function getOrders(): array { return $this->orders; }
    public function setOrders(array $orders): void { $this->orders = $orders; }

    // Business methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
```

#### Available Annotations

| Annotation | Description |
|------------|-------------|
| `@Entity` | Marks class as entity |
| `@Column` | Maps property to column |
| `@Id` | Primary key |
| `@Transient` | Exclude from persistence |
| `@OneToMany` | One-to-many relationship |
| `@ManyToOne` | Many-to-one relationship |
| `@ManyToMany` | Many-to-many relationship |

#### Usage

```php
// Create
$user = new User();
$user->setName('John');
$user->setEmail('john@example.com');
$user->save();

// Find
$user = User::find(1);

// Find by criteria
$admins = User::findBy(['status' => 'active']);
$user = User::findOneBy(['email' => 'john@example.com']);

// Update
$user->setName('Updated');
$user->save();

// Delete
$user->delete();

// Pagination
$page = User::repository()->paginate(page: 1, perPage: 15);

// Query builder with entities
$users = Orm::table('users')
    ->where('status', '=', 'active')
    ->entity(User::class)
    ->get();
```

---

## Configuration

### Environment Variables

Create `.env` file:

```
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=myapp
DB_USERNAME=root
DB_PASSWORD=secret
```

### Multiple Connections

```json
{
    "default": "mysql",
    "connections": {
        "mysql": { ... },
        "pgsql": { ... },
        "sqlite": { ... }
    }
}
```

Switch connection:

```php
ConnectionManager::connection('pgsql')->table('users')->get();
```

---

## Architect Framework Integration

### Option 1: Service Provider

In `appbootstrap.php`:

```php
<?php

use Axiom\Orm\Integrations\Architect\OrmServiceProvider;

$configPath = APP_DIR . 'config/database.json';
if (file_exists($configPath)) {
    $config = json_decode(file_get_contents($configPath), true);
    OrmServiceProvider::bootstrap($config['connections'] ?? $config);
}
```

### Option 2: Use Trait in Model

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

---

## License

MIT
