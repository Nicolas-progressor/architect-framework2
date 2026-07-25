<?php

declare(strict_types=1);

namespace Architect\Auth;

use Architect\Auth\Models\Role;
use Architect\Auth\Models\User;

/**
 * Auth Manager
 *
 * Основной класс для управления аутентификацией и авторизацией.
 *
 * @package Architect\Auth
 */
class AuthManager
{
    /**
     * Имя сессии для пользователя
     */
    private const SESSION_KEY = 'auth_user_id';

    /**
     * Имя сессии для JWT токена
     */
    private const JWT_SESSION_KEY = 'auth_jwt';

    /**
     * Текущий пользователь (кэш)
     */
    protected ?User $user = null;

    /**
     * Конфигурация
     */
    protected array $config = [];

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->loadConfig();
        $this->startSession();
    }

    /**
     * Загрузить конфигурацию
     */
    protected function loadConfig(): void
    {
        // Загружаем базовую конфигурацию из app/config/auth.json
        try {
            $container = \Architect\Core\Container::getInstance();
            if ($container->has('config')) {
                $config = $container->get('config');
                $this->config = $config->get('auth', []);
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // Загружаем конфигурацию авторизации для конкретного приложения
        $this->loadAppAuthConfig();

        // Значения по умолчанию
        $defaultConfig = [
            'driver' => 'database',
            'table_prefix' => 'auth_',
            'session_lifetime' => 1440,
            'password_hash_algorithm' => 'bcrypt',
            'password_cost' => 12,
            'jwt_secret' => 'change-me-in-production',
            'jwt_ttl' => 3600,
            'default_role' => 'guest',
            'urls' => [
                'login' => '/login',
                'logout' => '/logout',
                'register' => '/register',
                'redirect_after_login' => '/',
                'redirect_after_logout' => '/',
                'redirect_after_register' => '/',
                'password_reset' => '/password-reset',
                'email_verification' => '/email-verify',
            ],
        ];

        // Мержим: дефолтная -> базовая -> приложения
        $this->config = array_replace_recursive($defaultConfig, $this->config);

        // Загружаем конфигурацию авторизации для конкретного приложения
        $this->loadAppAuthConfig();
    }

    /**
     * Загрузить конфигурацию авторизации для конкретного приложения
     * Переопределяются: urls, roles, permissions, driver, session_lifetime и др.
     */
    protected function loadAppAuthConfig(): void
    {
        try {
            $container = \Architect\Core\Container::getInstance();

            // Получаем текущее приложение
            if (!$container->has('apps')) {
                return;
            }

            $apps = $container->get('apps');
            $appDir = $apps->appdir ?? '';

            if (empty($appDir)) {
                return;
            }

            // Проверяем наличие config/auth.json в папке приложения
            $appAuthConfigFile = $appDir . 'config/auth.json';

            if (file_exists($appAuthConfigFile)) {
                $appAuthConfig = json_decode(file_get_contents($appAuthConfigFile), true);

                if (is_array($appAuthConfig)) {
                    // Мержим конфиг приложения с основным
                    // array_replace_recursive работает для вложенных массивов
                    $this->config = array_replace_recursive($this->config, $appAuthConfig);
                }
            }
        } catch (\Exception $e) {
            // Ignore - конфиг приложения опциональный
        }
    }

    /**
     * Получить настройки ролей для приложения
     *
     * @return array
     */
    public function getRoles(): array
    {
        return $this->config['roles'] ?? [];
    }

    /**
     * Получить настройки разрешений для приложения
     *
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->config['permissions'] ?? [];
    }

    /**
     * Получить драйвер авторизации
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->config['driver'] ?? 'database';
    }

    /**
     * Получить время жизни сессии
     *
     * @return int
     */
    public function getSessionLifetime(): int
    {
        return (int) ($this->config['session_lifetime'] ?? 1440);
    }

    /**
     * Получить роль по умолчанию
     *
     * @return string
     */
    public function getDefaultRole(): string
    {
        return $this->config['default_role'] ?? 'guest';
    }

    /**
     * Включена ли JWT авторизация
     *
     * @return bool
     */
    public function isJwtEnabled(): bool
    {
        return !empty($this->config['jwt_secret']);
    }

    /**
     * Получить имя текущего приложения
     *
     * @return string
     */
    public function getCurrentApp(): string
    {
        try {
            $container = \Architect\Core\Container::getInstance();
            if ($container->has('apps')) {
                $apps = $container->get('apps');
                return $apps->app ?? '';
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return '';
    }

    /**
     * Получить директорию текущего приложения
     *
     * @return string
     */
    public function getCurrentAppDir(): string
    {
        try {
            $container = \Architect\Core\Container::getInstance();
            if ($container->has('apps')) {
                $apps = $container->get('apps');
                return $apps->appdir ?? '';
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return '';
    }

    /**
     * Запустить сессию
     */
    protected function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Аутентифицировать пользователя
     *
     * @param string $username Имя пользователя или email
     * @param string $password Пароль
     * @return bool True при успехе
     */
    public function login(string $username, string $password): bool
    {
        // Ищем пользователя
        $user = User::findByUsername($username);

        if (!$user) {
            $user = User::findByEmail($username);
        }

        if (!$user) {
            $this->logFailedAttempt($username, 'user_not_found');
            return false;
        }

        // Проверяем пароль
        if (!$user->verifyPassword($password)) {
            $this->logFailedAttempt($username, 'wrong_password');
            return false;
        }

        // Успешная аутентификация
        $this->loginUser($user);

        return true;
    }

    /**
     * Войти как пользователь (без пароля)
     *
     * @param User $user
     * @return void
     */
    public function loginUser(User $user): void
    {
        $this->user = $user;
        $_SESSION[self::SESSION_KEY] = $user->getId();

        // Генерируем JWT если настроен
        if (!empty($this->config['jwt_secret'])) {
            $jwt = $this->generateJWT($user);
            $_SESSION[self::JWT_SESSION_KEY] = $jwt;
        }

        $this->logEvent('user_login', ['user_id' => $user->getId()]);
    }

    /**
     * Выйти из системы
     *
     * @return void
     */
    public function logout(): void
    {
        if ($this->user) {
            $this->logEvent('user_logout', ['user_id' => $this->user->getId()]);
        }

        $this->user = null;
        unset($_SESSION[self::SESSION_KEY]);
        unset($_SESSION[self::JWT_SESSION_KEY]);
    }

    /**
     * Проверить, авторизован ли пользователь
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        if ($this->user !== null) {
            return true;
        }

        if (!isset($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        // Загружаем пользователя
        $userId = $_SESSION[self::SESSION_KEY];
        $this->user = User::find($userId);

        return $this->user !== null;
    }

    /**
     * Получить текущего пользователя
     *
     * @return User|null
     */
    public function getUser(): ?User
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->user;
    }

    /**
     * Получить ID текущего пользователя
     *
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->user?->getId();
    }

    /**
     * Проверить, имеет ли пользователь разрешение
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        $user = $this->getUser();

        if (!$user) {
            return false;
        }

        return $user->hasPermission($permission);
    }

    /**
     * Проверить, имеет ли пользователь роль
     *
     * @param string $roleName
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        $user = $this->getUser();

        if (!$user) {
            return false;
        }

        return $user->hasRole($roleName);
    }

    /**
     * Проверить, является ли админом
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Назначить роль пользователю
     *
     * @param User $user
     * @param string $roleName
     * @return bool
     */
    public function assignRole(User $user, string $roleName): bool
    {
        $role = Role::findByName($roleName);

        if (!$role) {
            return false;
        }

        $user->setRole($role);

        return $user->save();
    }

    /**
     * Отозвать роль у пользователя
     *
     * @param User $user
     * @param string $roleName
     * @return bool
     */
    public function revokeRole(User $user, string $roleName): bool
    {
        // Логика отзыва роли
        // Для ролей из конфига - просто меняем на default
        if ($user->hasRole($roleName)) {
            $defaultRole = $this->config['default_role'] ?? 'guest';
            return $this->assignRole($user, $defaultRole);
        }

        return false;
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
    public function register(string $username, string $email, string $password, ?string $role = null): ?User
    {
        // Проверяем, не занят ли username
        if (User::findByUsername($username)) {
            return null;
        }

        // Проверяем, не занят ли email
        if (User::findByEmail($email)) {
            return null;
        }

        $role ??= $this->config['default_role'] ?? 'guest';

        $user = User::create([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'role' => $role,
        ]);

        if ($user) {
            $this->logEvent('user_registered', ['user_id' => $user->getId()]);
        }

        return $user;
    }

    /**
     * Получить JWT токен
     *
     * @return string|null
     */
    public function getJWT(): ?string
    {
        return $_SESSION[self::JWT_SESSION_KEY] ?? null;
    }

    /**
     * Сгенерировать JWT токен
     *
     * @param User $user
     * @return string
     */
    protected function generateJWT(User $user): string
    {
        $payload = [
            'sub' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'iat' => time(),
            'exp' => time() + ($this->config['jwt_ttl'] ?? 3600),
        ];

        $secret = $this->config['jwt_secret'] ?? 'secret';

        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payloadEncoded = base64_encode(json_encode($payload));
        $signature = base64_encode(hash_hmac('sha256', "{$header}.{$payloadEncoded}", $secret, true));

        return "{$header}.{$payloadEncoded}.{$signature}";
    }

    /**
     * Проверить JWT токен
     *
     * @param string $token
     * @return array|false
     */
    public function verifyJWT(string $token): array|false
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }

        [$header, $payload, $signature] = $parts;

        // Проверяем подпись
        $secret = $this->config['jwt_secret'] ?? 'secret';
        $expectedSignature = base64_encode(hash_hmac('sha256', "{$header}.{$payload}", $secret, true));

        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }

        // Декодируем payload
        $payloadData = json_decode(base64_decode($payload), true);

        // Проверяем срок действия
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return false;
        }

        return $payloadData;
    }

    /**
     * Записать событие в лог
     *
     * @param string $event
     * @param array $context
     * @return void
     */
    protected function logEvent(string $event, array $context = []): void
    {
        try {
            $container = \Architect\Core\Container::getInstance();
            if ($container->has('logger')) {
                $logger = $container->get('logger');
                $logger->debug("Auth: {$event}", array_merge($context, ['source' => 'auth']));
            }
        } catch (\Exception $e) {
            // Ignore
        }
    }

    /**
     * Записать неудачную попытку входа
     *
     * @param string $username
     * @param string $reason
     * @return void
     */
    protected function logFailedAttempt(string $username, string $reason): void
    {
        $this->logEvent('login_failed', [
            'username' => $username,
            'reason' => $reason,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
    }

    /**
     * Получить конфигурацию
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Получить URL для входа
     *
     * @return string
     */
    public function getLoginUrl(): string
    {
        return $this->config['urls']['login'] ?? '/login';
    }

    /**
     * Получить URL для выхода
     *
     * @return string
     */
    public function getLogoutUrl(): string
    {
        return $this->config['urls']['logout'] ?? '/logout';
    }

    /**
     * Получить URL для регистрации
     *
     * @return string
     */
    public function getRegisterUrl(): string
    {
        return $this->config['urls']['register'] ?? '/register';
    }

    /**
     * Получить URL для редиректа после входа
     *
     * @return string
     */
    public function getRedirectAfterLogin(): string
    {
        return $this->config['urls']['redirect_after_login'] ?? '/';
    }

    /**
     * Получить URL для редиректа после выхода
     *
     * @return string
     */
    public function getRedirectAfterLogout(): string
    {
        return $this->config['urls']['redirect_after_logout'] ?? '/';
    }

    /**
     * Получить URL для редиректа после регистрации
     *
     * @return string
     */
    public function getRedirectAfterRegister(): string
    {
        return $this->config['urls']['redirect_after_register'] ?? '/';
    }

    /**
     * Получить URL для сброса пароля
     *
     * @return string
     */
    public function getPasswordResetUrl(): string
    {
        return $this->config['urls']['password_reset'] ?? '/password-reset';
    }

    /**
     * Получить URL для подтверждения email
     *
     * @return string
     */
    public function getEmailVerificationUrl(): string
    {
        return $this->config['urls']['email_verification'] ?? '/email-verify';
    }

    /**
     * Получить URL для редиректа с текущим URL
     *
     * @param string $defaultUrl URL по умолчанию
     * @return string
     */
    public function getRedirectUrl(string $defaultUrl = ''): string
    {
        $redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? $_SESSION['redirect'] ?? $defaultUrl;
        unset($_SESSION['redirect']);
        return $redirect;
    }

    /**
     * Установить URL для редиректа
     *
     * @param string $url
     * @return void
     */
    public function setRedirectUrl(string $url): void
    {
        $_SESSION['redirect'] = $url;
    }

    /**
     * Сгенерировать URL входа с редиректом
     *
     * @param string|null $redirect URL для редиректа после входа
     * @return string
     */
    public function loginUrl(?string $redirect = null): string
    {
        $url = $this->getLoginUrl();

        if ($redirect) {
            $url .= '?redirect=' . urlencode($redirect);
        } elseif ($currentUrl = $_SERVER['REQUEST_URI'] ?? null) {
            $url .= '?redirect=' . urlencode($currentUrl);
        }

        return $url;
    }

    /**
     * Сгенерировать URL выхода с редиректом
     *
     * @param string|null $redirect URL для редиректа после выхода
     * @return string
     */
    public function logoutUrl(?string $redirect = null): string
    {
        $url = $this->getLogoutUrl();

        if ($redirect) {
            $url .= '?redirect=' . urlencode($redirect);
        }

        return $url;
    }
}
