<?php

declare(strict_types=1);

namespace Architect\Services\Cache\Drivers;

use Architect\Services\Cache\Contracts\CacheInterface;
use DateInterval;
use InvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

/**
 * Abstract cache driver providing common functionality.
 */
abstract class AbstractCacheDriver implements CacheInterface
{
    /**
     * Default TTL in seconds (1 hour).
     */
    protected const DEFAULT_TTL = 3600;

    /**
     * Prefix for cache keys.
     */
    protected string $prefix = '';

    /**
     * Set a prefix for cache keys.
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * Get the prefixed key.
     */
    protected function prefixKey(string $key): string
    {
        if ($this->prefix === '') {
            return $key;
        }
        return $this->prefix . $key;
    }

    /**
     * Normalize a cache key.
     *
     * @throws PsrInvalidArgumentException
     */
    protected function normalizeKey(string $key): string
    {
        if ($key === '') {
            throw new class extends InvalidArgumentException implements PsrInvalidArgumentException {
                // Custom exception for PSR-16 compliance
            };
        }

        // Remove characters that are problematic for most backends
        if (!preg_match('/^[a-zA-Z0-9_\.\-]+$/', $key)) {
            throw new class extends InvalidArgumentException implements PsrInvalidArgumentException {
                // Custom exception for PSR-16 compliance
            };
        }

        return $key;
    }

    /**
     * Normalize TTL to seconds.
     *
     * @param null|int|DateInterval $ttl
     * @return null|int TTL in seconds, or null for forever.
     */
    protected function normalizeTtl(null|int|DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if (is_int($ttl)) {
            return $ttl > 0 ? $ttl : 0;
        }

        if ($ttl instanceof DateInterval) {
            $now = new \DateTimeImmutable();
            $then = $now->add($ttl);
            return $then->getTimestamp() - $now->getTimestamp();
        }

        throw new InvalidArgumentException('TTL must be null, an integer, or a DateInterval.');
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * Validate keys iterable.
     *
     * @throws PsrInvalidArgumentException
     */
    protected function validateKeys(iterable $keys): void
    {
        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new class extends InvalidArgumentException implements PsrInvalidArgumentException {
                    // Custom exception for PSR-16 compliance
                };
            }
            $this->normalizeKey($key);
        }
    }

    /**
     * Validate values iterable.
     *
     * @throws PsrInvalidArgumentException
     */
    protected function validateValues(iterable $values): void
    {
        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                throw new class extends InvalidArgumentException implements PsrInvalidArgumentException {
                    // Custom exception for PSR-16 compliance
                };
            }
            $this->normalizeKey($key);
        }
    }
}
