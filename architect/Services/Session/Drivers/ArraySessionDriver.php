<?php

declare(strict_types=1);

namespace Architect\Services\Session\Drivers;

use Architect\Services\Session\Contracts\SessionDriverInterface;

/**
 * In-memory session driver for testing.
 */
class ArraySessionDriver implements SessionDriverInterface
{
    private string $sessionId = '';
    private string $sessionName = 'architect_array';
    private int $lifetime = 1440;
    private bool $started = false;
    private array $data = [];

    private static int $idCounter = 0;

    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        if ($this->sessionId === '') {
            $this->sessionId = 'test_' . (++self::$idCounter);
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
        if ($deleteOld) {
            $this->data = [];
        }
        $this->sessionId = 'test_' . (++self::$idCounter);
        return true;
    }

    public function destroy(): bool
    {
        $this->data = [];
        $this->started = false;
        $this->sessionId = '';
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
        return $this->started;
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

    /**
     * Reset ID counter (for testing).
     */
    public static function resetCounter(): void
    {
        self::$idCounter = 0;
    }
}
