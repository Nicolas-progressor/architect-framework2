<?php

namespace Architect\AuthSystem\Services\OAuth2;

use Architect\AuthSystem\Contracts\OAuthProviderInterface;
use Architect\AuthSystem\Contracts\UserProviderInterface;
use Architect\AuthSystem\Events\EventDispatcherInterface;
use Architect\AuthSystem\Models\User;
use Architect\Core\Container;

/**
 * Менеджер OAuth2 аутентификации.
 */
class OAuthManager
{
    private array $providers = [];

    public function __construct(
        private UserProviderInterface $userProvider,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private array $config = []
    ) {
        $this->loadConfig();
        $this->registerBuiltInProviders();
    }

    /**
     * Загрузить конфигурацию OAuth.
     */
    private function loadConfig(): void
    {
        try {
            $container = Container::getInstance();
            if ($container->has('config')) {
                $config = $container->get('config');
                $this->config = $config->get('auth.oauth', []);
            }
        } catch (\Exception $e) {
            // Конфигурация не загружена
        }
    }

    /**
     * Зарегистрировать встроенные провайдеры.
     */
    private function registerBuiltInProviders(): void
    {
        foreach ($this->config as $name => $providerConfig) {
            if (empty($providerConfig['client_id']) || empty($providerConfig['client_secret'])) {
                continue;
            }

            $provider = $this->createProvider($name, $providerConfig);
            if ($provider) {
                $this->providers[$name] = $provider;
            }
        }
    }

    /**
     * Создать провайдер по имени.
     */
    private function createProvider(string $name, array $config): ?OAuthProviderInterface
    {
        $classMap = [
            'google' => GoogleOAuthProvider::class,
            'github' => GitHubOAuthProvider::class,
        ];

        $className = $classMap[$name] ?? null;
        if (!$className || !class_exists($className)) {
            return null;
        }

        return new $className($config);
    }

    /**
     * Получить провайдер по имени.
     */
    public function getProvider(string $name): ?OAuthProviderInterface
    {
        return $this->providers[$name] ?? null;
    }

    /**
     * Получить список зарегистрированных провайдеров.
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Аутентификация через OAuth.
     *
     * @param string $providerName
     * @param string $code
     * @return User|null
     */
    public function authenticate(string $providerName, string $code): ?User
    {
        $provider = $this->getProvider($providerName);
        if (!$provider) {
            throw new \InvalidArgumentException("Провайдер {$providerName} не найден.");
        }

        $tokenData = $provider->exchangeCodeForToken($code);
        $accessToken = $tokenData['access_token'] ?? null;
        if (!$accessToken) {
            throw new \RuntimeException('Не удалось получить токен доступа.');
        }

        $userInfo = $provider->getUserInfo($accessToken);
        $user = $this->findOrCreateUser($providerName, $userInfo);

        // Диспетчеризация события
        if ($this->eventDispatcher) {
            // TODO: создать событие OAuthLoginEvent
        }

        return $user;
    }

    /**
     * Найти или создать пользователя на основе данных OAuth.
     */
    private function findOrCreateUser(string $providerName, array $userInfo): User
    {
        $oauthId = $userInfo['id'] ?? null;
        $email = $userInfo['email'] ?? null;

        // Поиск по OAuth ID
        $user = $this->userProvider->findByOAuthId($providerName, $oauthId);
        if ($user) {
            return $user;
        }

        // Поиск по email
        if ($email) {
            $user = $this->userProvider->findByEmail($email);
            if ($user) {
                // Привязать OAuth ID к существующему пользователю
                $user->addOAuthId($providerName, $oauthId);
                $user->save();
                return $user;
            }
        }

        // Создать нового пользователя
        $user = new User();
        $user->setEmail($email ?? '');
        $user->setUsername($userInfo['login'] ?? $userInfo['name'] ?? 'oauth_user_' . $oauthId);
        $user->setPassword(bin2hex(random_bytes(16))); // случайный пароль
        $user->setRole($this->userProvider->getDefaultRole());
        $user->addOAuthId($providerName, $oauthId);
        $user->save();

        return $user;
    }

    /**
     * Получить URL для авторизации через провайдера.
     */
    public function getAuthorizationUrl(string $providerName, array $scopes = [], ?string $state = null): string
    {
        $provider = $this->getProvider($providerName);
        if (!$provider) {
            throw new \InvalidArgumentException("Провайдер {$providerName} не найден.");
        }

        return $provider->getAuthorizationUrl($scopes, $state);
    }
}
