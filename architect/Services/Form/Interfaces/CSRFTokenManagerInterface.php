<?php

declare(strict_types=1);

namespace Architect\Services\Form\Interfaces;

/**
 * Interface CSRFTokenManagerInterface
 * 
 * Интерфейс менеджера CSRF-токенов.
 */
interface CSRFTokenManagerInterface
{
    /**
     * Получить или создать CSRF-токен для формы
     * 
     * @param string $formName Имя формы
     * @param int $ttl Время жизни токена в секундах
     * @return string CSRF-токен
     */
    public function generateToken(string $formName, int $ttl = 3600): string;

    /**
     * Проверить CSRF-токен
     * 
     * @param string $formName Имя формы
     * @param string $token Токен для проверки
     * @return bool True если токен валиден
     */
    public function validateToken(string $formName, string $token): bool;

    /**
     * Удалить токен для формы
     * 
     * @param string $formName Имя формы
     * @return void
     */
    public function removeToken(string $formName): void;

    /**
     * Получить HTML-скрытое поле с CSRF-токеном
     * 
     * @param string $formName Имя формы
     * @return string HTML-скрытое поле
     */
    public function getTokenField(string $formName): string;

    /**
     * Получить мета-тег с CSRF-токеном для AJAX
     * 
     * @param string $formName Имя формы
     * @return string HTML-мета тег
     */
    public function getMetaTag(string $formName = 'default'): string;

    /**
     * Очистить все просроченные токены
     * 
     * @return int Количество удалённых токенов
     */
    public function cleanExpiredTokens(): int;
}
