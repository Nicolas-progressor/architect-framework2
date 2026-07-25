# Axiom ORM - Database Migrations

Universal SQL query constructor with migrations support for PHP.

## Installation

```bash
composer require axiom/migration
```

## Quick Start

### 1. Create Migration File

```bash
php vendor/bin/axiom migration:make create_users_table
```

Or using Architect Console:

```bash
php arc make:migration create_users_table
```

Or manually:

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

### 2. Run Migrations

```php
<?php

use Axiom\Migration\MigrationManager;

$manager = new MigrationManager(__DIR__ . '/migrations');
$manager->migrate();
```

### 3. Available Commands

Using Composer:

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

Using Architect Console (`arc`):

```bash
# Run all pending migrations
php arc db:migrate

# Rollback last migration
php arc db:rollback

# Reset all migrations
php arc db:reset

# Show migration status
php arc db:status

# Create new migration
php arc make:migration create_orders_table

# With options
php arc db:migrate --force           # Force in production
php arc db:migrate --pretend         # Show what would run
php arc db:migrate --step=5          # Run 5 migrations
php arc make:migration create_users_table --create
php arc make:migration add_column --modify
```

## Blueprint API

```php
$table->id();                    // BIGINT AUTO_INCREMENT PRIMARY KEY
$table->increments('id');        // INT AUTO_INCREMENT PRIMARY KEY
$table->bigInteger('amount');    // BIGINT
$table->integer('count');        // INT
$table->smallInteger('num');     // SMALLINT
$table->tinyInteger('flag');     // TINYINT
$table->string('name', 255);     // VARCHAR(255)
$table->text('description');     // TEXT
$table->longText('content');     // LONGTEXT
$table->mediumText('data');      // MEDIUMTEXT
$table->json('options');         // JSON
$table->boolean('active');       // BOOLEAN
$table->date('birthday');        // DATE
$table->dateTime('created_at');  // DATETIME
$table->timestamp('updated_at'); // TIMESTAMP
$table->time('start_time');      // TIME
$table->decimal('price', 10, 2); // DECIMAL(10,2)
$table->float('rate');           // FLOAT
$table->double('amount');        // DOUBLE
$table->enum('status', ['a', 'b']); // ENUM
$table->uuid('id');              // CHAR(36)
$table->timestamps();            // created_at + updated_at

// Modifiers
$table->string('name')->nullable();
$table->string('name')->default('John');
$table->integer('count')->unsigned();
$table->string('email')->unique();
$table->string('name')->comment('User name');

// Indexes
$table->index('email');
$table->unique(['email', 'tenant_id']);
$table->primary(['id', 'tenant_id']);

// Foreign keys
$table->bigInteger('user_id')
    ->foreign('user_id')
    ->references('id')
    ->on('users')
    ->onDelete('CASCADE');
```

## Migration Manager API

```php
$manager = new MigrationManager($path, $table = 'migrations');

// Run all pending migrations
$ran = $manager->migrate();

// Rollback last batch
$rolledBack = $manager->rollback();

// Reset all migrations
$reset = $manager->reset();

// Get status
$status = $manager->status();

// Get pending migrations
$pending = $manager->getPendingMigrations();

// Create migration file
$filename = MigrationManager::create('create_users_table', $path);
```

## Configuration

The migrations table will be created automatically. It includes:
- `id` - Auto-increment ID
- `migration` - Migration filename
- `batch` - Batch number for rollback grouping
- `created_at` - Timestamp

## Integration with Architect Console

Commands are automatically available after installing `axiom/migration`:

| Command | Description |
|---------|-------------|
| `db:migrate` | Run pending migrations |
| `db:rollback` | Rollback last batch |
| `db:reset` | Reset all migrations |
| `db:status` | Show migration status |
| `make:migration` | Create new migration |

### Configuration Search Order

Console commands search for database config in:
1. `app/config/database.json`
2. `config/database.json`
3. `database.json`

## Transactions in Migrations

Migrations are automatically wrapped in transactions for MySQL (InnoDB) and PostgreSQL. For SQLite, use `.mode transaction` if needed.

## License

MIT
