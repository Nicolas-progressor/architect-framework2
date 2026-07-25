<?php

declare(strict_types=1);

namespace Architect\Auth\Services;

use Architect\Core\Container;

class ConfigService
{
    private array $config = [];
    private bool $loaded = false;

    public function __construct()
    {
        $this->load();
    }

    /**
     * Загрузить конфигурацию auth из контейнера Architect и приложения.
     */
    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->config = $this->loadGlobalConfig();
        $this->mergeAppConfig();
        $this->mergeDefaults();
        $this->loaded = true;
    }

    /**
     * Загрузить глобальную конфигурацию из app/config/auth.json.
     */
    private function loadGlobalConfig(): array
    {
        try {
            $container = Container::getInstance();
            if ($container->has('config')) {
                $config = $container->get('config');
                return $config->get('auth', []);
            }
        } catch (\Exception $e) {
            // Конфигурация не доступна
        }

        return [];
    }

    /**
     * Загрузить конфигурацию из текущего приложения (apps/{app}/config/auth.json).
     */
    private function mergeAppConfig(): void
    {
        try {
            $container = Container::getInstance();
            if (!$container->has('apps')) {
                return;
            }

            $apps = $container->get('apps');
            $appDir = $apps->appdir ?? '';
            if (empty($appDir)) {
                return;
            }

            $appAuthConfigFile = $appDir . 'config/auth.json';
            if (file_exists($appAuthConfigFile)) {
                $appConfig = json_decode(file_get_contents($appAuthConfigFile), true);
                if (is_array($appConfig)) {
                    $this->config = array_replace_recursive($this->config, $appConfig);
                }
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки загрузки конфига приложения
        }
    }

    /**
     * Смержить с дефолтными значениями.
     */
    private function mergeDefaults(): void
    {
        $defaults = [
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
            'roles' => [
                'admin' => [
                    'permissions' => ['*'],
                    'description' => 'Администратор с полным доступом',
                ],
                'moderator' => [
                    'permissions' => ['view_posts', 'create_posts', 'edit_posts', 'delete_posts', 'manage_comments'],
                    'description' => 'Модератор',
                ],
                'user' => [
                    'permissions' => ['view_posts', 'create_posts', 'edit_own_posts', 'comment'],
                    'description' => 'Обычный пользователь',
                ],
                'guest' => [
                    'permissions' => ['view_public_content'],
                    'description' => 'Гость',
                ],
            ],
            'permissions' => [
                'view_public_content' => 'Просмотр публичного контента',
                'view_posts' => 'Просмотр постов',
                'create_posts' => 'Создание постов',
                'edit_own_posts' => 'Редактирование своих постов',
                'edit_posts' => 'Редактирование любых постов',
                'delete_own_posts' => 'Удаление своих постов',
                'delete_posts' => 'Удаление любых постов',
                'comment' => 'Комментирование',
                'manage_comments' => 'Управление комментариями',
                'manage_users' => 'Управление пользователями',
                'manage_roles' => 'Управление ролями',
                'access_admin' => 'Доступ к админ-панели',
            ],
        ];

        $this->config = array_replace_recursive($defaults, $this->config);
    }

    /**
     * Получить значение конфигурации по ключу.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Получить всю конфигурацию auth.
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Получить роли.
     */
    public function getRoles(): array
    {
        return $this->get('roles', []);
    }

    /**
     * Получить разрешения.
     */
    public function getPermissions(): array
    {
        return $this->get('permissions', []);
    }

    /**
     * Получить роль по умолчанию.
     */
    public function getDefaultRole(): string
    {
        return $this->get('default_role', 'guest');
    }

    /**
     * Получить JWT секрет.
     */
    public function getJwtSecret(): string
    {
        return $this->get('jwt_secret', 'change-me-in-production');
    }

    /**
     * Получить время жизни JWT.
     */
    public function getJwtTtl(): int
    {
        return (int) $this->get('jwt_ttl', 3600);
    }

    /**
     * Получить URL по ключу.
     */
    public function getUrl(string $key, string $default = '/'): string
    {
        return $this->get("urls.{$key}", $default);
    }

    /**
     * Получить все URL.
     */
    public function getUrls(): array
    {
        return $this->get('urls', []);
    }
}