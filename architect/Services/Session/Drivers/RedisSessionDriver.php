<?php

declare(strict_types=1);

namespace Architect\Services\Session\Drivers;

use Architect\Services\Session\Contracts\SessionDriverInterface;

/**
 * Redis session driver.
 *
 * Uses PHP's ext-redis or falls back to ext-predis if available.
 */
class RedisSessionDriver implements SessionDriverInterface
{
    private string $sessionId = '';
    private string $sessionName = 'architect_redis';
    private int $lifetime = 1440;
    private bool $started = false;
    private array $data = [];

    /** @var \Redis|null */
    private ?\Redis $redis = null;

    private string $prefix = 'session:';

    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly int $port = 6379,
        private readonly ?string $password = null,
        private readonly int $db = 0,
        ?int $lifetime = null,
    ) {
        if ($lifetime !== null) {
            $this->lifetime = $lifetime;
        }
    }

    /**
     * Set external Redis instance (for testing).
     */
    public function setRedis(\Redis $redis): void
    {
        $this->redis = $redis;
    }

    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        $this->connect();

        if ($this->sessionId === '') {
            $this->sessionId = $this->generateId();
        }

        $raw = $this->redis->get($this->prefix . $this->sessionId);
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $this->data = $decoded;
            }
        }

        $this->started = true;
        return true;
    }

    public function isActive(): bool
    {
        return $this->started;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function all(): array
    {
        return $this->data;
    }

    public function put(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    public function forget(array $keys): void
    {
        foreach ($keys as $key) {
            unset($this->data[$key]);
        }
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function getId(): string
    {
        return $this->sessionId;
    }

    public function setId(string $id): void
    {
        $this->sessionId = $id;
    }

    public function regenerate(bool $deleteOld = true): bool
    {
        if ($deleteOld && $this->redis !== null) {
            $this->redis->del($this->prefix . $this->sessionId);
        }

        $this->sessionId = $this->generateId();
        return true;
    }

    public function destroy(): bool
    {
        $this->connect();

        if ($this->redis !== null) {
            $this->redis->del($this->prefix . $this->sessionId);
        }

        $this->data = [];
        $this->started = false;
        return true;
    }

    public function getName(): string
    {
        return $this->sessionName;
    }

    public function setName(string $name): void
    {
        $this->sessionName = $name;
    }

    public function getLifetime(): int
    {
        return $this->lifetime;
    }

    public function setLifetime(int $seconds): void
    {
        $this->lifetime = $seconds;
    }

    public function save(): bool
    {
        if (!$this->started || $this->redis === null) {
            return false;
        }

        $json = json_encode($this->data, JSON_THROW_ON_ERROR);
        $key = $this->prefix . $this->sessionId;

        $this->redis->setex($key, $this->lifetime, $json);
        return true;
    }

    public function meta(): array
    {
        return [
            'id' => $this->sessionId,
            'name' => $this->sessionName,
            'lifetime' => $this->lifetime,
            'active' => $this->started,
        ];
    }

    private function connect(): void
    {
        if ($this->redis !== null) {
            return;
        }

        if (!class_exists(\Redis::class)) {
            throw new \RuntimeException(
                'Redis extension not available. Install php-redis or provide Redis instance via setRedis().'
            );
        }

        $this->redis = new \Redis();
        $connected = $this->redis->connect($this->host, $this->port, 2.0);

        if (!$connected) {
            throw new \RuntimeException("Cannot connect to Redis at {$this->host}:{$this->port}");
        }

        if ($this->password !== null) {
            $this->redis->auth($this->password);
        }

        $this->redis->select($this->db);
    }

    private function generateId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
