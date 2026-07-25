<?php

declare(strict_types=1);

namespace Architect\Auth\Contracts;

interface TokenStorageInterface
{
    /**
     * Сохранить токен для пользователя.
     *
     * @param string $key
     * @param string $token
     * @param int|null $ttl Время жизни в секундах
     * @return bool
     */
    public function store(string $key, string $token, ?int $ttl = null): bool;

    /**
     * Получить токен по ключу.
     *
     * @param string $key
     * @return string|null
     */
    public function retrieve(string $key): ?string;

    /**
     * Удалить токен.
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool;

    /**
     * Проверить, существует ли токен.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Очистить все токены.
     *
     * @return void
     */
    public function clear(): void;
}