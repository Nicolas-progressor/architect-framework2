<?php

declare(strict_types=1);

namespace Architect\AuthSystem;

use Architect\AuthSystem\Contracts\AuthenticationInterface;
use Architect\AuthSystem\Contracts\AuthorizationInterface;
use Architect\AuthSystem\Contracts\TokenStorageInterface;
use Architect\AuthSystem\Contracts\UserProviderInterface;
use Architect\AuthSystem\Events\AuthEventDispatcher;
use Architect\AuthSystem\Events\EventDispatcherInterface;
use Architect\AuthSystem\Middleware\AuthMiddleware;
use Architect\AuthSystem\Middleware\GuestMiddleware;
use Architect\AuthSystem\Middleware\RoleMiddleware;
use Architect\AuthSystem\Repositories\UserRepository;
use Architect\AuthSystem\Services\AuthenticationService;
use Architect\AuthSystem\Services\AuthorizationService;
use Architect\AuthSystem\Services\ConfigService;
use Architect\AuthSystem\Services\JwtTokenService;
use Architect\AuthSystem\Services\OAuth2\OAuthManager;
use Architect\AuthSystem\Services\SessionStorage;
use Architect\Contracts\ServiceProviderInterface;
use Architect\Core\Contracts\ContainerInterface;

class AuthServiceProvider implements ServiceProviderInterface
{
    /**
     * Зарегистрировать сервисы в контейнере.
     */
    public function register(ContainerInterface $container): void
    {
        // Конфигурация
        $container->singleton(ConfigService::class, function ($c) {
            return new ConfigService();
        });

        // Диспетчер событий
        $container->singleton(EventDispatcherInterface::class, function ($c) {
            return new AuthEventDispatcher();
        });

        // Хранилище токенов (сессии)
        $container->singleton(TokenStorageInterface::class, function ($c) {
            return new SessionStorage();
        });

        // JWT сервис
        $container->singleton(JwtTokenService::class, function ($c) {
            $config = $c->get(ConfigService::class);
            return new JwtTokenService(
                $config->getJwtSecret(),
                $config->getJwtTtl()
            );
        });

        // Репозиторий пользователей
        $container->singleton(UserProviderInterface::class, function ($c) {
            return new UserRepository();
        });

        // Сервис авторизации
        $container->singleton(AuthorizationInterface::class, function ($c) {
            return new AuthorizationService(
                $c->get(EventDispatcherInterface::class)
            );
        });

        // Сервис аутентификации
        $container->singleton(AuthenticationInterface::class, function ($c) {
            return new AuthenticationService(
                $c->get(UserProviderInterface::class),
                $c->get(TokenStorageInterface::class),
                $c->get(EventDispatcherInterface::class),
                $c->has('logger') ? $c->get('logger') : null
            );
        });

        // OAuth Manager
        $container->singleton(OAuthManager::class, function ($c) {
            return new OAuthManager(
                $c->get(UserProviderInterface::class),
                $c->get(EventDispatcherInterface::class)
            );
        });

        // Middleware
        $container->singleton(AuthMiddleware::class, function ($c) {
            return new AuthMiddleware(
                $c->get(AuthenticationInterface::class),
                $c->get(AuthorizationInterface::class)
            );
        });

        $container->singleton(GuestMiddleware::class, function ($c) {
            return new GuestMiddleware(
                $c->get(AuthenticationInterface::class)
            );
        });

        $container->singleton(RoleMiddleware::class, function ($c) {
            return new RoleMiddleware(
                $c->get(AuthenticationInterface::class),
                $c->get(AuthorizationInterface::class)
            );
        });

        // Алиас для обратной совместимости (опционально)
        $container->alias('auth', AuthenticationInterface::class);
        $container->alias('auth.authorization', AuthorizationInterface::class);
        $container->alias('auth.user_provider', UserProviderInterface::class);
        $container->alias('auth.event_dispatcher', EventDispatcherInterface::class);
        $container->alias('auth.oauth', OAuthManager::class);
    }

    /**
     * Загрузить сервисы после регистрации.
     */
    public function boot(ContainerInterface $container): void
    {
        // Ничего не требуется
    }

    /**
     * Загрузить конфигурацию по умолчанию.
     */
    public static function getDefaultConfig(): array
    {
        return [
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
            ],
            'oauth' => [
                'google' => [
                    'client_id' => '',
                    'client_secret' => '',
                    'redirect_uri' => '/oauth/google/callback',
                ],
                'github' => [
                    'client_id' => '',
                    'client_secret' => '',
                    'redirect_uri' => '/oauth/github/callback',
                ],
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
                'manage_users' => 'Управление пользователей',
                'manage_roles' => 'Управление ролей',
                'access_admin' => 'Доступ к админ-панели',
            ],
        ];
    }
}
