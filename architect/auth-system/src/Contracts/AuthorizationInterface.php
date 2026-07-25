<?php

declare(strict_types=1);

namespace Architect\Auth\Contracts;

use Architect\Auth\Models\User;

interface AuthorizationInterface
{
    /**
     * Проверить, имеет ли пользователь указанное разрешение.
     *
     * @param User $user
     * @param string $permission
     * @return bool
     */
    public function hasPermission(User $user, string $permission): bool;

    /**
     * Проверить, имеет ли пользователь указанную роль.
     *
     * @param User $user
     * @param string $role
     * @return bool
     */
    public function hasRole(User $user, string $role): bool;

    /**
     * Назначить роль пользователю.
     *
     * @param User $user
     * @param string $role
     * @return bool Успешность операции
     */
    public function assignRole(User $user, string $role): bool;

    /**
     * Отозвать роль у пользователя.
     *
     * @param User $user
     * @param string $role
     * @return bool Успешность операции
     */
    public function revokeRole(User $user, string $role): bool;

    /**
     * Получить все роли пользователя.
     *
     * @param User $user
     * @return array<string>
     */
    public function getRoles(User $user): array;

    /**
     * Получить все разрешения пользователя.
     *
     * @param User $user
     * @return array<string>
     */
    public function getPermissions(User $user): array;
}