<?php

declare(strict_types=1);

namespace Axiom\Cache;

use Axiom\Orm\Query\QueryBuilder;

/**
 * Cache manager for query results
 */
class CacheManager
{
    private static ?CacheDriver $driver = null;
    private static array $config = [];
    private static bool $enabled = true;
    private static string $prefix = 'axiom_';

    /**
     * Configure cache
     */
    public static function configure(array $config): void
    {
        self::$config = $config;
        self::$prefix = $config['prefix'] ?? 'axiom_';
        self::$enabled = $config['enabled'] ?? true;

        if (self::$enabled && self::$driver === null) {
            self::$driver = self::createDriver($config);
        }
    }

    /**
     * Get config
     */
    public static function getConfig(): array
    {
        return self::$config;
    }

    /**
     * Check if cache is enabled
     */
    public static function isEnabled(): bool
    {
        return self::$enabled && self::$driver !== null;
    }

    /**
     * Get driver instance
     */
    public static function getDriver(): ?CacheDriver
    {
        return self::$driver;
    }

    /**
     * Create cache driver
     */
    private static function createDriver(array $config): CacheDriver
    {
        $driver = $config['driver'] ?? 'array';
        $ttl = $config['ttl'] ?? 3600;

        return match ($driver) {
            'redis' => new RedisCacheDriver($config['redis'] ?? [], $ttl),
            'memcached' => new MemcachedCacheDriver($config['memcached'] ?? [], $ttl),
            'apcu' => new ApcuCacheDriver($ttl),
            'file' => new FileCacheDriver($config['file'] ?? [], $ttl),
            'architect' => new ArchitectCacheDriver($config['architect'] ?? [], $ttl),
            default => new ArrayCacheDriver($ttl),
        };
    }

    /**
     * Generate cache key from query
     */
    public static function generateKey(QueryBuilder $query): string
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        return self::$prefix . md5($sql . serialize($bindings));
    }

    /**
     * Get value from cache
     */
    public static function get(string $key): mixed
    {
        if (!self::isEnabled()) {
            return null;
        }

        return self::$driver->get($key);
    }

    /**
     * Set value in cache
     */
    public static function set(string $key, mixed $value, ?int $ttl = null): void
    {
        if (!self::isEnabled()) {
            return;
        }

        self::$driver->set($key, $value, $ttl);
    }

    /**
     * Check if key exists
     */
    public static function has(string $key): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        return self::$driver->has($key);
    }

    /**
     * Delete key from cache
     */
    public static function delete(string $key): void
    {
        if (!self::isEnabled()) {
            return;
        }

        self::$driver->delete($key);
    }

    /**
     * Clear all cache
     */
    public static function flush(): void
    {
        if (!self::isEnabled()) {
            return;
        }

        self::$driver->flush();
    }

    /**
     * Clear cache by pattern
     */
    public static function flushPattern(string $pattern): void
    {
        if (!self::isEnabled()) {
            return;
        }

        self::$driver->flushPattern($pattern);
    }

    /**
     * Disable cache
     */
    public static function disable(): void
    {
        self::$enabled = false;
    }

    /**
     * Enable cache
     */
    public static function enable(): void
    {
        self::$enabled = true;
    }

    /**
     * Reset driver (for testing)
     */
    public static function reset(): void
    {
        self::$driver = null;
        self::$config = [];
        self::$enabled = true;
    }
}

/**
 * Cache driver interface
 */
interface CacheDriver
{
    /**
     * Get value from cache
     */
    public function get(string $key): mixed;

    /**
     * Set value in cache
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void;

    /**
     * Check if key exists
     */
    public function has(string $key): bool;

    /**
     * Delete key from cache
     */
    public function delete(string $key): void;

    /**
     * Clear all cache
     */
    public function flush(): void;

    /**
     * Clear cache by pattern
     */
    public function flushPattern(string $pattern): void;
}

/**
 * Array cache driver (for testing/development)
 */
class ArrayCacheDriver implements CacheDriver
{
    private array $store = [];
    private int $ttl;

    public function __construct(int $ttl = 3600)
    {
        $this->ttl = $ttl;
    }

    public function get(string $key): mixed
    {
        if (!isset($this->store[$key])) {
            return null;
        }

        $item = $this->store[$key];
        
        if ($item['expires'] < time()) {
            unset($this->store[$key]);
            return null;
        }

        return $item['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->store[$key] = [
            'value' => $value,
            'expires' => time() + ($ttl ?? $this->ttl)
        ];
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): void
    {
        unset($this->store[$key]);
    }

    public function flush(): void
    {
        $this->store = [];
    }

    public function flushPattern(string $pattern): void
    {
        $keys = array_keys($this->store);
        foreach ($keys as $key) {
            if (fnmatch($pattern, $key)) {
                unset($this->store[$key]);
            }
        }
    }
}

/**
 * APCu cache driver
 */
class ApcuCacheDriver implements CacheDriver
{
    private int $ttl;

    public function __construct(int $ttl = 3600)
    {
        $this->ttl = $ttl;
    }

    public function get(string $key): mixed
    {
        $value = apcu_fetch($key, $success);
        return $success ? $value : null;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        apcu_store($key, $value, $ttl ?? $this->ttl);
    }

    public function has(string $key): bool
    {
        return apcu_exists($key);
    }

    public function delete(string $key): void
    {
        apcu_delete($key);
    }

    public function flush(): void
    {
        apcu_clear_cache();
    }

    public function flushPattern(string $pattern): void
    {
        // APCu doesn't support pattern deletion, iterate through keys
        $info = apcu_cache_info(true);
        if (!empty($info['cache_list'])) {
            foreach ($info['cache_list'] as $entry) {
                if (fnmatch($pattern, $entry['key'])) {
                    apcu_delete($entry['key']);
                }
            }
        }
    }
}

/**
 * Redis cache driver
 */
class RedisCacheDriver implements CacheDriver
{
    private \Redis $redis;
    private int $ttl;

    public function __construct(array $config, int $ttl = 3600)
    {
        $this->ttl = $ttl;
        
        $this->redis = new \Redis();
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 6379;
        
        $this->redis->connect($host, $port);
        
        if (!empty($config['password'])) {
            $this->redis->auth($config['password']);
        }
        
        if (!empty($config['database'])) {
            $this->redis->select($config['database']);
        }
    }

    public function get(string $key): mixed
    {
        $value = $this->redis->get($key);
        return $value !== false ? unserialize($value) : null;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->redis->setex($key, $ttl ?? $this->ttl, serialize($value));
    }

    public function has(string $key): bool
    {
        return $this->redis->exists($key) > 0;
    }

    public function delete(string $key): void
    {
        $this->redis->del($key);
    }

    public function flush(): void
    {
        $this->redis->flushDB();
    }

    public function flushPattern(string $pattern): void
    {
        $pattern = str_replace('*', '*', $pattern);
        $keys = $this->redis->keys($pattern);
        
        if (!empty($keys)) {
            $this->redis->del($keys);
        }
    }
}

/**
 * Memcached cache driver
 */
class MemcachedCacheDriver implements CacheDriver
{
    private \Memcached $memcached;
    private int $ttl;

    public function __construct(array $config, int $ttl = 3600)
    {
        $this->ttl = $ttl;
        
        $this->memcached = new \Memcached();
        
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 11211;
        
        $this->memcached->addServer($host, $port);
    }

    public function get(string $key): mixed
    {
        $value = $this->memcached->get($key);
        
        if ($this->memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
            return null;
        }
        
        return $value;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->memcached->set($key, $value, $ttl ?? $this->ttl);
    }

    public function has(string $key): bool
    {
        $this->memcached->get($key);
        return $this->memcached->getResultCode() !== \Memcached::RES_NOTFOUND;
    }

    public function delete(string $key): void
    {
        $this->memcached->delete($key);
    }

    public function flush(): void
    {
        $this->memcached->flush();
    }

    public function flushPattern(string $pattern): void
    {
        // Memcached doesn't support pattern deletion
        // Would need to track keys manually
    }
}

/**
 * File cache driver
 */
class FileCacheDriver implements CacheDriver
{
    private string $path;
    private int $ttl;

    public function __construct(array $config, int $ttl = 3600)
    {
        $this->ttl = $ttl;
        $this->path = $config['path'] ?? sys_get_temp_dir() . '/axiom_cache';
        
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    private function getFilePath(string $key): string
    {
        $hash = md5($key);
        return $this->path . '/' . $hash . '.cache';
    }

    public function get(string $key): mixed
    {
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($file));
        
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $file = $this->getFilePath($key);
        
        $data = [
            'value' => $value,
            'expires' => time() + ($ttl ?? $this->ttl)
        ];
        
        file_put_contents($file, serialize($data));
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): void
    {
        $file = $this->getFilePath($key);
        
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function flush(): void
    {
        $files = glob($this->path . '/*.cache');
        
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function flushPattern(string $pattern): void
    {
        $files = glob($this->path . '/*.cache');
        
        foreach ($files as $file) {
            $key = basename($file, '.cache');
            if (fnmatch(str_replace('*', '', $pattern), $key)) {
                unlink($file);
            }
        }
    }
}
