<?php

declare(strict_types=1);

namespace Architect\Services\Config;

use Architect\Services\Config\Contracts\ConfigInterface;
use Architect\Services\Config\Contracts\MutableConfigInterface;

/**
 * Configuration repository with dot notation support.
 * 
 * Immutable by default, implements MutableConfigInterface for runtime modifications.
 */
final class ConfigRepository implements MutableConfigInterface
{
    /**
     * Create configuration repository.
     * 
     * @param array $data Configuration data
     */
    public function __construct(
        private array $data = []
    ) {}

    /**
     * @inheritdoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->data;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function has(string $key): bool
    {
        $keys = explode('.', $key);
        $value = $this->data;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return false;
            }
            $value = $value[$k];
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * @inheritdoc
     */
    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$this->data;

        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $current[$k] = $value;
                return;
            }

            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }

            $current = &$current[$k];
        }
    }

    /**
     * @inheritdoc
     */
    public function forget(string $key): void
    {
        $keys = explode('.', $key);
        $lastKey = array_pop($keys);
        $current = &$this->data;

        foreach ($keys as $k) {
            if (!is_array($current) || !array_key_exists($k, $current)) {
                return;
            }
            $current = &$current[$k];
        }

        unset($current[$lastKey]);
    }

    /**
     * @inheritdoc
     */
    public function replace(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Create a new immutable copy with merged data.
     * 
     * @param array $data Data to merge
     * @return self
     */
    public function merge(array $data): self
    {
        return new self(array_replace_recursive($this->data, $data));
    }

    /**
     * Create an immutable copy (removes mutation methods).
     * 
     * @return ConfigInterface
     */
    public function toImmutable(): ConfigInterface
    {
        return new self($this->data);
    }
}
