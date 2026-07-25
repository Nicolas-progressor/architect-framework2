<?php

declare(strict_types=1);

namespace Architect\AuthSystem\Services;

use Architect\AuthSystem\Contracts\AuthenticationInterface;
use Architect\AuthSystem\Contracts\TokenStorageInterface;
use Architect\AuthSystem\Contracts\UserProviderInterface;
use Architect\AuthSystem\Events\EventDispatcherInterface;
use Architect\AuthSystem\Events\FailedAuthenticationEvent;
use Architect\AuthSystem\Events\LoginEvent;
use Architect\AuthSystem\Events\LogoutEvent;
use Architect\AuthSystem\Events\RegisterEvent;
use Architect\AuthSystem\Models\User;
use Architect\Core\Container;
use Psr\Log\LoggerInterface;

class AuthenticationService implements AuthenticationInterface
{
    private const SESSION_KEY = 'auth_user_id';
    private const JWT_SESSION_KEY = 'auth_jwt';

    private ?User $user = null;
    private array $config = [];

    public function __construct(
        private UserProviderInterface $userProvider,
        private TokenStorageInterface $tokenStorage,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private ?LoggerInterface $logger = null
    ) {
        $this->loadConfig();
        $this->startSession();
    }

    /**
     * Загрузить конфигурацию из контейнера Architect.
     */
    private function loadConfig(): void
    {
        try {
            $container = Container::getInstance();
            if ($container->has('config')) {
                $config = $container->get('config');
                $this->config = $config->get('auth', []);
            }
        } catch (\Exception $e) {
            // Конфигурация не загружена, используем значения по умолчанию
        }

        $defaults = [
            'driver' => 'database',
            'session_lifetime' => 1440,
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
        ];

        $this->config = array_replace_recursive($defaults, $this->config);
    }

    /**
     * Запустить сессию, если ещё не запущена.
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login(string $username, string $password): bool
    {
        $user = $this->userProvider->findByUsername($username)
            ?? $this->userProvider->findByEmail($username);

        if (!$user) {
            $this->logFailedAttempt($username, 'user_not_found');
            $this->dispatchFailed($username, 'user_not_found');
            return false;
        }

        if (!$user->verifyPassword($password)) {
            $this->logFailedAttempt($username, 'wrong_password');
            $this->dispatchFailed($username, 'wrong_password');
            return false;
        }

        $this->loginUser($user);
        return true;
    }

    public function loginUser(User $user): void
    {
        $this->user = $user;
        $_SESSION[self::SESSION_KEY] = $user->getId();

        // Генерация JWT, если настроен секрет
        if (!empty($this->config['jwt_secret'])) {
            $jwt = $this->generateJWT($user);
            $_SESSION[self::JWT_SESSION_KEY] = $jwt;
        }

        $this->logEvent('user_login', ['user_id' => $user->getId()]);
        $this->dispatchLogin($user);
    }

    public function logout(): void
    {
        if ($this->user) {
            $this->logEvent('user_logout', ['user_id' => $this->user->getId()]);
            $this->dispatchLogout($this->user);
        }

        $this->user = null;
        unset($_SESSION[self::SESSION_KEY], $_SESSION[self::JWT_SESSION_KEY]);
    }

    public function isLoggedIn(): bool
    {
        if ($this->user !== null) {
            return true;
        }

        if (!isset($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        $userId = $_SESSION[self::SESSION_KEY];
        $this->user = $this->userProvider->findById($userId);

        return $this->user !== null;
    }

    public function getUser(): ?User
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->user;
    }

    public function getUserId(): ?int
    {
        return $this->user?->getId();
    }

    /**
     * Сгенерировать JWT токен для пользователя.
     */
    private function generateJWT(User $user): string
    {
        $payload = [
            'sub' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'iat' => time(),
            'exp' => time() + ($this->config['jwt_ttl'] ?? 3600),
        ];

        return \Firebase\JWT\JWT::encode($payload, $this->config['jwt_secret'], 'HS256');
    }

    /**
     * Записать событие в лог.
     */
    private function logEvent(string $event, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->debug("Auth: {$event}", array_merge($context, ['source' => 'auth']));
        }
    }

    /**
     * Записать неудачную попытку входа.
     */
    private function logFailedAttempt(string $username, string $reason): void
    {
        $this->logEvent('login_failed', [
            'username' => $username,
            'reason' => $reason,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
    }

    /**
     * Диспетчеризация события успешного входа.
     */
    private function dispatchLogin(User $user): void
    {
        if (!$this->eventDispatcher) {
            return;
        }

        $event = new LoginEvent(
            $user->toArray(),
            'web',
            'password'
        );
        $this->eventDispatcher->dispatch(LoginEvent::NAME, $event);
    }

    /**
     * Диспетчеризация события выхода.
     */
    private function dispatchLogout(User $user): void
    {
        if (!$this->eventDispatcher) {
            return;
        }

        $event = new LogoutEvent(
            $user->toArray(),
            'web'
        );
        $this->eventDispatcher->dispatch(LogoutEvent::NAME, $event);
    }

    /**
     * Диспетчеризация события неудачной аутентификации.
     */
    private function dispatchFailed(string $identifier, string $reason): void
    {
        if (!$this->eventDispatcher) {
            return;
        }

        $event = new FailedAuthenticationEvent(
            $identifier,
            $reason,
            'web'
        );
        $this->eventDispatcher->dispatch(FailedAuthenticationEvent::NAME, $event);
    }

    /**
     * Диспетчеризация события регистрации.
     */
    public function dispatchRegister(User $user): void
    {
        if (!$this->eventDispatcher) {
            return;
        }

        $event = new RegisterEvent(
            $user->toArray(),
            'web'
        );
        $this->eventDispatcher->dispatch(RegisterEvent::NAME, $event);
    }

    /**
     * Получить конфигурацию.
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
