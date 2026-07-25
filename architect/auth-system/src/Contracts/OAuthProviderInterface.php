<?php

namespace Architect\AuthSystem\Contracts;

/**
 * Интерфейс для OAuth2 провайдеров.
 */
interface OAuthProviderInterface
{
    /**
     * Получить URL для авторизации.
     *
     * @param array $scopes Запрашиваемые области доступа
     * @param string|null $state Параметр состояния для защиты от CSRF
     * @return string
     */
    public function getAuthorizationUrl(array $scopes = [], ?string $state = null): string;

    /**
     * Обмен кода авторизации на токен доступа.
     *
     * @param string $code
     * @return array Токен доступа и данные
     */
    public function exchangeCodeForToken(string $code): array;

    /**
     * Получить информацию о пользователе.
     *
     * @param string $accessToken
     * @return array Данные пользователя
     */
    public function getUserInfo(string $accessToken): array;

    /**
     * Обновить токен доступа.
     *
     * @param string $refreshToken
     * @return array Новый токен
     */
    public function refreshToken(string $refreshToken): array;

    /**
     * Проверить валидность токена.
     *
     * @param string $accessToken
     * @return bool
     */
    public function validateToken(string $accessToken): bool;

    /**
     * Получить имя провайдера (например, 'google', 'github').
     *
     * @return string
     */
    public function getName(): string;
}