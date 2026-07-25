# Axiom ORM - Many-to-Many Relationships

Many-to-many relationships support for Axiom ORM.

## Installation

```bash
composer require axiom/many-to-many
```

## Quick Start

### Define Many-to-Many Relationship

```php
<?php

use Axiom\ManyToMany\BelongsToManyTrait;

class User
{
    use BelongsToManyTrait;
    
    private ?int $id = null;
    private string $table = 'users';
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    protected function getTable(): string
    {
        return $this->table;
    }
    
    // Define relationship
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }
}

class Role
{
    use BelongsToManyTrait;
    
    private ?int $id = null;
    private string $table = 'roles';
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    protected function getTable(): string
    {
        return $this->table;
    }
}
```

## Usage

### Attach Related Models

```php
$user = User::find(1);

// Attach single role
$user->roles()->attach(3);

// Attach multiple roles
$user->roles()->attach([1, 2, 3]);

// Attach with pivot data
$user->roles()->attach(3, ['assigned_at' => now()]);
```

### Detach Related Models

```php
$user = User::find(1);

// Detach single role
$user->roles()->detach(3);

// Detach multiple roles
$user->roles()->detach([1, 2]);

// Detach all roles
$user->roles()->detach();
```

### Sync Related Models

```php
$user = User::find(1);

// Keep only these roles (remove others)
$user->roles()->sync([1, 2, 3]);

// Sync and keep pivot data
$user->roles()->sync([
    1 => ['assigned_at' => now()],
    2 => ['assigned_at' => now()]
]);
```

### Toggle Related Models

```php
$user = User::find(1);

// Toggle roles (attach if not exists, detach if exists)
$user->roles()->toggle([1, 2, 3]);
```

### Get Related Models

```php
$user = User::find(1);

// Get all roles
$roles = $user->roles()->get();

// Get first role
$role = $user->roles()->first();

// Count roles
$count = $user->roles()->count();
```

### Check if Related

```php
$user = User::find(1);

// Check if user has role
if ($user->roles()->has(3)) {
    // User has role with ID 3
}
```

### Update Pivot Data

```php
$user = User::find(1);

// Update pivot data
$user->roles()->updatePivot(3, [
    'assigned_at' => now(),
    'assigned_by' => 'admin'
]);
```

### With Pivot Columns

```php
// When defining relationship
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class)
        ->withPivot(['assigned_at', 'assigned_by']);
}

// Get roles with pivot data
$roles = $user->roles()->get();

foreach ($roles as $role) {
    echo $role->getName();
    echo $role->pivot_assigned_at;  // Pivot data available
}
```

## Pivot Table Structure

The default pivot table naming follows alphabetical order:

```
User + Role -> pivot table: roles_users
User + Permission -> pivot table: permissions_users
```

### Custom Pivot Table

```php
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class, 'user_roles');
}
```

## Using with Repository/Entity

```php
use Axiom\Entity\Repository;
use Axiom\ManyToMany\BelongsToMany;

class UserRepository extends Repository
{
    /**
     * Get user with roles
     */
    public function getWithRoles(int $id): ?object
    {
        $user = $this->find($id);
        
        if ($user) {
            // Load roles through relationship
            $roles = (new BelongsToMany(Role::class))
                ->setParent(
                    \Axiom\Orm\Orm::table($this->getTable()),
                    $id
                )
                ->using('user_roles')
                ->get();
            
            // Attach to user (implementation depends on your entity)
            $user->setRoles($roles);
        }
        
        return $user;
    }
}
```

## Many-to-Many with Extra Columns

For pivot tables with extra columns:

```php
// Migration for pivot table with extra columns
$this->create('user_roles', function (Blueprint $table) {
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('role_id');
    $table->timestamp('assigned_at');
    $table->string('assigned_by', 100);
    
    $table->primary(['user_id', 'role_id']);
    $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
    $table->foreign('role_id')->references('id')->on('roles')->onDelete('CASCADE');
});

// Using with extra columns
$user->roles()->attach(3, [
    'assigned_at' => new \DateTime(),
    'assigned_by' => 'admin'
]);
```

## Best Practices

1. **Use sync() for bulk updates**: More efficient than attach/detach for multiple changes
2. **Invalidate cache on relationship changes**: If using cache module
3. **Use transactions**: For multiple relationship changes

```php
\Axiom\Orm\Orm::transaction(function () use ($user, $roles) {
    $user->roles()->sync($roles);
});
```

## License

MIT
