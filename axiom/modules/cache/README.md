# Axiom ORM - Query Cache

Query caching support for Axiom ORM with multiple backend support.

## Installation

```bash
composer require axiom/cache
```

## Configuration

### JSON Configuration

```json
{
    "cache": {
        "driver": "redis",
        "enabled": true,
        "ttl": 3600,
        "prefix": "axiom_",
        "redis": {
            "host": "127.0.0.1",
            "port": 6379,
            "password": null,
            "database": 0
        },
        "memcached": {
            "host": "127.0.0.1",
            "port": 11211
        },
        "file": {
            "path": "/tmp/axiom_cache"
        }
    }
}
```

### PHP Configuration

```php
use Axiom\Cache\Cache;

Cache::configure([
    'driver' => 'redis',
    'ttl' => 3600,
    'prefix' => 'myapp_',
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379
    ]
]);
```

## Supported Drivers

| Driver | Description |
|--------|-------------|
| `array` | In-memory array (default, for testing) |
| `apcu` | APCu user cache |
| `redis` | Redis (requires ext-redis) |
| `memcached` | Memcached (requires ext-memcached) |
| `file` | File system cache |

## Usage with Query Builder

### Basic Caching

```php
use Axiom\Orm\Orm;

// Cache query results for 5 minutes
$users = Orm::table('users')
    ->where('status', '=', 'active')
    ->cache(300)  // 5 minutes in seconds
    ->get();

// Or use default TTL from config
$users = Orm::table('users')
    ->where('status', '=', 'active')
    ->cache()
    ->get();
```

### Custom Cache Key

```php
$users = Orm::table('users')
    ->where('status', '=', 'active')
    ->remember('popular_users', 600)  // Custom key with TTL
    ->get();
```

### Disable Cache for Query

```php
$users = Orm::table('users')
    ->where('status', '=', 'active')
    ->disableCache()  // Bypass cache
    ->get();
```

### Using Cache Helper

```php
use Axiom\Cache\Cache;

// Set value
Cache::set('my_key', ['data' => 'value'], 300);

// Get value
$data = Cache::get('my_key');

// Check if exists
if (Cache::has('my_key')) {
    // ...
}

// Delete value
Cache::forget('my_key');

// Clear all cache
Cache::flush();

// Clear by pattern
Cache::flushPattern('users_*');
```

## Cache Invalidation

### Automatic Invalidation

The cache key is generated from the SQL query and bindings. To invalidate:

1. **Time-based**: Use TTL
2. **Manual**: Use `remember()` with custom key and delete when data changes

```php
// When user data changes
Cache::forget('popular_users');

// Or flush pattern when bulk update
Cache::flushPattern('users_*');
```

### Event-based Invalidation

```php
// In your model after update
public function updateUser($id, $data)
{
    // Update database
    $this->db()->table('users')->update(...)->execute();
    
    // Invalidate cache
    Cache::flushPattern('users_*');
}
```

## Integration with Entity

```php
use Axiom\Cache\Cache;

// Configure first
Cache::configure(['driver' => 'redis', 'ttl' => 3600]);

// Then use with entity queries
$users = Orm::table('users')
    ->cache(600)
    ->entity(User::class)
    ->get();
```

## Best Practices

1. **Use appropriate TTL**: Short TTL for frequently changing data, long TTL for static data
2. **Use custom keys**: For complex queries, use `remember()` with descriptive keys
3. **Invalidate on updates**: Clear related cache when data changes
4. **Monitor cache hit rate**: Track cache performance

## Example: Caching with Pagination

```php
public function getUsersPage($page = 1, $perPage = 15)
{
    $cacheKey = "users_page_{$page}_{$perPage}";
    
    return Cache::remember($cacheKey, 300, function () use ($page, $perPage) {
        return Orm::table('users')
            ->orderBy('created_at', 'DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();
    });
}

// Invalidate on user creation
public function createUser($data)
{
    $this->db()->table('users')->insert(...)->execute();
    Cache::flushPattern('users_page_*');
}
```

## License

MIT
