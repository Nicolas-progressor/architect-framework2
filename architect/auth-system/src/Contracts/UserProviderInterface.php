<?php

declare(strict_types=1);

namespace Architect\Auth\Contracts;

use Architect\Auth\Models\User;

interface UserProviderInterface
{
    /**
     * Найти пользователя по ID.
     *
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User;

    /**
     * Найти пользователя по имени пользователя.
     *
     * @param string $username
     * @return User|null
     */
    public function findByUsername(string $username): ?User;

    /**
     * Найти пользователя по email.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * Создать нового пользователя.
     *
     * @param array $data
     * @return User|null
     */
    public function create(array $data): ?User;

    /**
     * Обновить пользователя.
     *
     * @param User $user
     * @param array $data
     * @return bool
     */
    public function update(User $user, array $data): bool;

    /**
     * Удалить пользователя.
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool;

    /**
     * Проверить, существует ли пользователь с указанным username.
     *
     * @param string $username
     * @return bool
     */
    public function usernameExists(string $username): bool;

    /**
     * Проверить, существует ли пользователь с указанным email.
     *
     * @param string $email
     * @return bool
     */
    public function emailExists(string $email): bool;
}