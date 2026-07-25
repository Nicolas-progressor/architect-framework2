<?php

declare(strict_types=1);

namespace Architect\Auth\Contracts;

use Architect\Auth\Models\User;

interface AuthenticationInterface
{
    /**
     * Аутентифицировать пользователя по username/email и паролю.
     *
     * @param string $username Имя пользователя или email
     * @param string $password Пароль
     * @return bool Успешность аутентификации
     */
    public function login(string $username, string $password): bool;

    /**
     * Войти как указанный пользователь (без проверки пароля).
     *
     * @param User $user
     * @return void
     */
    public function loginUser(User $user): void;

    /**
     * Выйти из системы.
     *
     * @return void
     */
    public function logout(): void;

    /**
     * Проверить, авторизован ли пользователь.
     *
     * @return bool
     */
    public function isLoggedIn(): bool;

    /**
     * Получить текущего аутентифицированного пользователя.
     *
     * @return User|null
     */
    public function getUser(): ?User;

    /**
     * Получить ID текущего пользователя.
     *
     * @return int|null
     */
    public function getUserId(): ?int;
}