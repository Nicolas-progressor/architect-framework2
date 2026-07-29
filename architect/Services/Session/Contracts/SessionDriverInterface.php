<?php

declare(strict_types=1);

namespace Architect\Services\Session\Contracts;

/**
 * Session driver interface.
 */
interface SessionDriverInterface
{
    /**
     * Start the session.
     */
    public function start(): bool;

    /**
     * Check if session is started.
     */
    public function isActive(): bool;

    /**
     * Get a value from session.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a value in session.
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if key exists.
     */
    public function has(string $key): bool;

    /**
     * Remove a value from session.
     */
    public function remove(string $key): void;

    /**
     * Get all session data.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Set multiple values at once.
     *
     * @param array<string, mixed> $data
     */
    public function put(array $data): void;

    /**
     * Remove multiple keys.
     *
     * @param array<int, string> $keys
     */
    public function forget(array $keys): void;

    /**
     * Clear all session data.
     */
    public function clear(): void;

    /**
     * Get session ID.
     */
    public function getId(): string;

    /**
     * Set session ID.
     */
    public function setId(string $id): void;

    /**
     * Regenerate session ID.
     */
    public function regenerate(bool $deleteOld = true): bool;

    /**
     * Destroy the session.
     */
    public function destroy(): bool;

    /**
     * Get session name.
     */
    public function getName(): string;

    /**
     * Set session name.
     */
    public function setName(string $name): void;

    /**
     * Get session lifetime in seconds.
     */
    public function getLifetime(): int;

    /**
     * Set session lifetime in seconds.
     */
    public function setLifetime(int $seconds): void;

    /**
     * Save session data.
     */
    public function save(): bool;

    /**
     * Get session meta info.
     *
     * @return array{id: string, name: string, lifetime: int, active: bool}
     */
    public function meta(): array;
}
