<?php

declare(strict_types=1);

namespace Architect\Services\Cache\Drivers;

/**
 * In-memory array cache driver.
 * Suitable for request‑lifecycle caching.
 */
class ArrayCacheDriver extends AbstractCacheDriver
{
    /**
     * Storage array: key => [value, expiration timestamp|null]
     *
     * @var array<string, array{mixed, ?int}>
     */
    private array $storage = [];

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->prefixKey($this->normalizeKey($key));

        if (!$this->has($key)) {
            return $default;
        }

        return $this->storage[$key][0];
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $key = $this->prefixKey($this->normalizeKey($key));
        $expiration = $this->normalizeTtl($ttl);

        if ($expiration !== null && $expiration <= 0) {
            // Immediate expiration = delete
            $this->delete($key);
            return true;
        }

        $expireAt = $expiration === null ? null : (time() + $expiration);
        $this->storage[$key] = [$value, $expireAt];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $key = $this->prefixKey($this->normalizeKey($key));
        unset($this->storage[$key]);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->storage = [];
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $key = $this->prefixKey($this->normalizeKey($key));

        if (!isset($this->storage[$key])) {
            return false;
        }

        [, $expireAt] = $this->storage[$key];
        if ($expireAt !== null && $expireAt < time()) {
            // Expired, remove it
            unset($this->storage[$key]);
            return false;
        }

        return true;
    }

    /**
     * Get number of items currently stored (including expired but not yet garbage‑collected).
     */
    public function count(): int
    {
        $this->garbageCollect();
        return count($this->storage);
    }

    /**
     * Remove expired entries.
     */
    private function garbageCollect(): void
    {
        $now = time();
        foreach ($this->storage as $key => [$value, $expireAt]) {
            if ($expireAt !== null && $expireAt < $now) {
                unset($this->storage[$key]);
            }
        }
    }
}
