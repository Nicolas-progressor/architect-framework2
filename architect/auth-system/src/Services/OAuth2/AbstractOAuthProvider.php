<?php

namespace Architect\AuthSystem\Services\OAuth2;

use Architect\AuthSystem\Contracts\OAuthProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Абстрактный OAuth2 провайдер с использованием Guzzle.
 */
abstract class AbstractOAuthProvider implements OAuthProviderInterface
{
    protected Client $httpClient;
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->httpClient = new Client([
            'timeout' => 10,
            'verify' => true,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizationUrl(array $scopes = [], ?string $state = null): string
    {
        $params = [
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'response_type' => 'code',
            'scope' => implode(' ', $scopes ?: $this->getDefaultScopes()),
            'state' => $state ?? bin2hex(random_bytes(16)),
        ];

        return $this->getBaseAuthorizationUrl() . '?' . http_build_query($params);
    }

    /**
     * @inheritDoc
     */
    public function exchangeCodeForToken(string $code): array
    {
        $params = [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'code' => $code,
            'redirect_uri' => $this->config['redirect_uri'],
            'grant_type' => 'authorization_code',
        ];

        try {
            $response = $this->httpClient->post($this->getTokenUrl(), [
                'form_params' => $params,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from OAuth provider');
            }

            return $data;
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to exchange code for token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function refreshToken(string $refreshToken): array
    {
        $params = [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ];

        try {
            $response = $this->httpClient->post($this->getTokenUrl(), [
                'form_params' => $params,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from OAuth provider');
            }

            return $data;
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to refresh token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function validateToken(string $accessToken): bool
    {
        try {
            $response = $this->httpClient->get($this->getValidationUrl(), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);

            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            return false;
        }
    }

    /**
     * Получить базовый URL для авторизации.
     *
     * @return string
     */
    abstract protected function getBaseAuthorizationUrl(): string;

    /**
     * Получить URL для обмена кода на токен.
     *
     * @return string
     */
    abstract protected function getTokenUrl(): string;

    /**
     * Получить URL для валидации токена.
     *
     * @return string
     */
    abstract protected function getValidationUrl(): string;

    /**
     * Получить URL для получения информации о пользователе.
     *
     * @return string
     */
    abstract protected function getUserInfoUrl(): string;

    /**
     * Получить список областей по умолчанию.
     *
     * @return array
     */
    abstract protected function getDefaultScopes(): array;
}
