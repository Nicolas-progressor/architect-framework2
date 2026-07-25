<?php

declare(strict_types=1);

namespace Architect\Services\Cache\Drivers;

use Redis;
use RedisException;
use InvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

/**
 * Redis cache driver.
 * Requires the phpredis extension or a compatible Redis client.
 */
class RedisCacheDriver extends AbstractCacheDriver
{
    /**
     * Redis client instance.
     */
    private Redis $redis;

    /**
     * Whether the connection is persistent.
     */
    private bool $persistent = false;

    /**
     * Create a new Redis cache driver.
     *
     * @param Redis $redis Connected Redis instance
     * @param bool  $persistent Whether the connection is persistent (for serialization)
     */
    public function __construct(Redis $redis, bool $persistent = false)
    {
        $this->redis = $redis;
        $this->persistent = $persistent;
    }

    /**
     * Create a RedisCacheDriver from configuration array.
     *
     * @param array $config Configuration with keys: host, port, password, database, timeout, persistent
     * @return self
     * @throws InvalidArgumentException If Redis extension not loaded or connection fails.
     */
    public static function fromConfig(array $config): self
    {
        if (!extension_loaded('redis')) {
            throw new InvalidArgumentException('Redis extension is not loaded.');
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 6379;
        $password = $config['password'] ?? null;
        $database = $config['database'] ?? 0;
        $timeout = $config['timeout'] ?? 0.0;
        $persistent = $config['persistent'] ?? false;
        $persistentId = $config['persistent_id'] ?? null;

        $redis = new Redis();

        $connectMethod = $persistent ? 'pconnect' : 'connect';
        $connected = $persistent && $persistentId
            ? $redis->{$connectMethod}($host, $port, $timeout, $persistentId)
            : $redis->{$connectMethod}($host, $port, $timeout);

        if (!$connected) {
            throw new InvalidArgumentException(
                sprintf('Cannot connect to Redis server at %s:%d', $host, $port)
            );
        }

        if ($password !== null && !$redis->auth($password)) {
            throw new InvalidArgumentException('Redis authentication failed.');
        }

        if ($database !== 0 && !$redis->select($database)) {
            throw new InvalidArgumentException('Cannot select Redis database.');
        }

        return new self($redis, $persistent);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->prefixKey($this->normalizeKey($key));

        try {
            $value = $this->redis->get($key);
        } catch (RedisException) {
            return $default;
        }

        if ($value === false) {
            return $default;
        }

        return unserialize($value, ['allowed_classes' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $key = $this->prefixKey($this->normalizeKey($key));
        $expiration = $this->normalizeTtl($ttl);

        $serialized = serialize($value);

        try {
            if ($expiration === null) {
                return $this->redis->set($key, $serialized);
            }

            if ($expiration <= 0) {
                // Immediate expiration = delete
                return $this->redis->del($key) > 0;
            }

            return $this->redis->setex($key, $expiration, $serialized);
        } catch (RedisException) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $key = $this->prefixKey($this->normalizeKey($key));

        try {
            return $this->redis->del($key) > 0;
        } catch (RedisException) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        try {
            return $this->redis->flushDB();
        } catch (RedisException) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $key = $this->prefixKey($this->normalizeKey($key));

        try {
            return $this->redis->exists($key) > 0;
        } catch (RedisException) {
            return false;
        }
    }

    /**
     * Get the underlying Redis client.
     */
    public function getClient(): Redis
    {
        return $this->redis;
    }

    /**
     * {@inheritdoc}
     */
    public function __destruct()
    {
        if (!$this->persistent) {
            try {
                $this->redis->close();
            } catch (RedisException) {
                // ignore
            }
        }
    }
}