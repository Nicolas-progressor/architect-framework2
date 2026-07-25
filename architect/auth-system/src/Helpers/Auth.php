<?php

declare(strict_types=1);

namespace Architect\Auth\Helpers;

use Architect\Auth\AuthManager;
use Architect\Auth\Models\User;

/**
 * Auth Helper
 *
 * Удобный статический доступ к функциям авторизации.
 *
 * Использование:
 *
 *   Auth::check()          // Авторизован ли пользователь
 *   Auth::user()           // Получить текущего пользователя
 *   Auth::id()             // Получить ID пользователя
 *   Auth::can('edit_post') // Проверить разрешение
 *   Auth::role('admin')    // Проверить роль
 *   Auth::login($u, $p)    // Войти
 *   Auth::logout()         // Выйти
 *
 * @package Architect\Auth\Helpers
 */
class Auth
{
    /**
     * Получить экземпляр AuthManager
     *
     * @return AuthManager
     */
    protected static function getManager(): AuthManager
    {
        static $manager = null;

        if ($manager === null) {
            $manager = new AuthManager();
        }

        return $manager;
    }

    /**
     * Проверить, авторизован ли пользователь
     *
     * @return bool
     */
    public static function check(): bool
    {
        return self::getManager()->isLoggedIn();
    }

    /**
     * Получить текущего пользователя
     *
     * @return User|null
     */
    public static function user(): ?User
    {
        return self::getManager()->getUser();
    }

    /**
     * Получить ID текущего пользователя
     *
     * @return int|null
     */
    public static function id(): ?int
    {
        return self::getManager()->getUserId();
    }

    /**
     * Проверить, имеет ли пользователь разрешение
     *
     * @param string $permission
     * @return bool
     */
    public static function can(string $permission): bool
    {
        return self::getManager()->hasPermission($permission);
    }

    /**
     * Проверить, имеет ли пользователь роль
     *
     * @param string $role
     * @return bool
     */
    public static function is(string $role): bool
    {
        return self::getManager()->hasRole($role);
    }

    /**
     * Проверить, является ли админом
     *
     * @return bool
     */
    public static function isAdmin(): bool
    {
        return self::getManager()->isAdmin();
    }

    /**
     * Войти по username и password
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public static function login(string $username, string $password): bool
    {
        return self::getManager()->login($username, $password);
    }

    /**
     * Войти как пользователь
     *
     * @param User $user
     * @return void
     */
    public static function loginUser(User $user): void
    {
        self::getManager()->loginUser($user);
    }

    /**
     * Выйти из системы
     *
     * @return void
     */
    public static function logout(): void
    {
        self::getManager()->logout();
    }

    /**
     * Зарегистрировать нового пользователя
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string|null $role
     * @return User|null
     */
    public static function register(string $username, string $email, string $password, ?string $role = null): ?User
    {
        return self::getManager()->register($username, $email, $password, $role);
    }

    /**
     * Назначить роль пользователю
     *
     * @param User $user
     * @param string $roleName
     * @return bool
     */
    public static function assignRole(User $user, string $roleName): bool
    {
        return self::getManager()->assignRole($user, $roleName);
    }

    /**
     * Отозвать роль у пользователя
     *
     * @param User $user
     * @param string $roleName
     * @return bool
     */
    public static function revokeRole(User $user, string $roleName): bool
    {
        return self::getManager()->revokeRole($user, $roleName);
    }

    /**
     * Получить JWT токен
     *
     * @return string|null
     */
    public static function getJWT(): ?string
    {
        return self::getManager()->getJWT();
    }

    /**
     * Проверить JWT токен
     *
     * @param string $token
     * @return array|false
     */
    public static function verifyJWT(string $token): array|false
    {
        return self::getManager()->verifyJWT($token);
    }

    /**
     * Получить URL для входа
     *
     * @return string
     */
    public static function loginUrl(): string
    {
        return self::getManager()->getLoginUrl();
    }

    /**
     * Получить URL для выхода
     *
     * @return string
     */
    public static function logoutUrl(): string
    {
        return self::getManager()->getLogoutUrl();
    }

    /**
     * Получить URL для регистрации
     *
     * @return string
     */
    public static function registerUrl(): string
    {
        return self::getManager()->getRegisterUrl();
    }

    /**
     * Получить URL для редиректа после входа
     *
     * @return string
     */
    public static function redirectAfterLogin(): string
    {
        return self::getManager()->getRedirectAfterLogin();
    }

    /**
     * Получить URL для редиректа после выхода
     *
     * @return string
     */
    public static function redirectAfterLogout(): string
    {
        return self::getManager()->getRedirectAfterLogout();
    }

    /**
     * Получить URL для редиректа после регистрации
     *
     * @return string
     */
    public static function redirectAfterRegister(): string
    {
        return self::getManager()->getRedirectAfterRegister();
    }

    /**
     * Сгенерировать URL входа с редиректом
     *
     * @param string|null $redirect URL для редиректа после входа
     * @return string
     */
    public static function loginLink(?string $redirect = null): string
    {
        return self::getManager()->loginUrl($redirect);
    }

    /**
     * Сгенерировать URL выхода с редиректом
     *
     * @param string|null $redirect URL для редиректа после выхода
     * @return string
     */
    public static function logoutLink(?string $redirect = null): string
    {
        return self::getManager()->logoutUrl($redirect);
    }

    /**
     * Установить URL для редиректа
     *
     * @param string $url
     * @return void
     */
    public static function setRedirectUrl(string $url): void
    {
        self::getManager()->setRedirectUrl($url);
    }

    /**
     * Получить URL для редиректа (из GET/POST/SESSION)
     *
     * @param string $defaultUrl URL по умолчанию
     * @return string
     */
    public static function getRedirectUrl(string $defaultUrl = ''): string
    {
        return self::getManager()->getRedirectUrl($defaultUrl);
    }

    /**
     * Получить имя текущего приложения
     *
     * @return string
     */
    public static function getCurrentApp(): string
    {
        return self::getManager()->getCurrentApp();
    }

    /**
     * Получить директорию текущего приложения
     *
     * @return string
     */
    public static function getCurrentAppDir(): string
    {
        return self::getManager()->getCurrentAppDir();
    }

    /**
     * Получить настройки ролей для приложения
     *
     * @return array
     */
    public static function getRoles(): array
    {
        return self::getManager()->getRoles();
    }

    /**
     * Получить настройки разрешений для приложения
     *
     * @return array
     */
    public static function getPermissions(): array
    {
        return self::getManager()->getPermissions();
    }

    /**
     * Получить драйвер авторизации
     *
     * @return string
     */
    public static function getDriver(): string
    {
        return self::getManager()->getDriver();
    }

    /**
     * Получить время жизни сессии
     *
     * @return int
     */
    public static function getSessionLifetime(): int
    {
        return self::getManager()->getSessionLifetime();
    }

    /**
     * Получить роль по умолчанию
     *
     * @return string
     */
    public static function getDefaultRole(): string
    {
        return self::getManager()->getDefaultRole();
    }

    /**
     * Включена ли JWT авторизация
     *
     * @return bool
     */
    public static function isJwtEnabled(): bool
    {
        return self::getManager()->isJwtEnabled();
    }
}
