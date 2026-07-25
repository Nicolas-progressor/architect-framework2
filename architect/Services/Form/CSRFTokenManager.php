<?php

declare(strict_types=1);

namespace Architect\Services\Form;

use Architect\Services\Form\Interfaces\CSRFTokenManagerInterface;
use Architect\Services\Form\Interfaces\SessionInterface;
use Architect\Services\Form\Traits\EscaperTrait;

/**
 * Class CSRFTokenManager
 *
 * Управление CSRF-токенами для защиты форм.
 * Реализует интерфейс CSRFTokenManagerInterface.
 *
 * @package Architect\Services\Form
 */
class CSRFTokenManager implements CSRFTokenManagerInterface
{
    use EscaperTrait;
    /**
     * Имя сессии для хранения CSRF-токенов
     */
    private const SESSION_KEY = 'csrf_tokens';

    /**
     * Время жизни токена по умолчанию (секунды)
     */
    private const DEFAULT_TTL = 3600;

    /**
     * Session interface
     */
    private SessionInterface $session;

    /**
     * Конструктор
     * 
     * @param SessionInterface|null $session
     */
    public function __construct(?SessionInterface $session = null)
    {
        $this->session = $session ?? new NativeSession();
    }

    /**
     * Получить или создать CSRF-токен для формы
     * 
     * @param string $formName Имя формы
     * @param int $ttl Время жизни токена в секундах
     * @return string CSRF-токен
     */
    public function generateToken(string $formName, int $ttl = self::DEFAULT_TTL): string
    {
        $tokens = $this->getTokens();
        
        // Проверяем, есть ли уже валидный токен
        if (isset($tokens[$formName]) && $this->isTokenValid($tokens[$formName])) {
            return $tokens[$formName]['token'];
        }

        // Генерируем новый токен
        $token = $this->generateRandomToken();
        $expiresAt = time() + $ttl;

        $tokens[$formName] = [
            'token' => $token,
            'expires_at' => $expiresAt,
            'created_at' => time(),
        ];

        $this->saveTokens($tokens);

        return $token;
    }

    /**
     * Проверить CSRF-токен
     * 
     * @param string $formName Имя формы
     * @param string $token Токен для проверки
     * @return bool True если токен валиден
     */
    public function validateToken(string $formName, string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        $tokens = $this->getTokens();

        if (!isset($tokens[$formName])) {
            return false;
        }

        $storedToken = $tokens[$formName];

        // Проверяем срок действия
        if (!$this->isTokenValid($storedToken)) {
            $this->removeToken($formName);
            return false;
        }

        // Проверяем токен
        return hash_equals($storedToken['token'], $token);
    }

    /**
     * Удалить токен для формы
     * 
     * @param string $formName Имя формы
     * @return void
     */
    public function removeToken(string $formName): void
    {
        $tokens = $this->getTokens();
        unset($tokens[$formName]);
        $this->saveTokens($tokens);
    }

    /**
     * Получить HTML-скрытое поле с CSRF-токеном
     * 
     * @param string $formName Имя формы
     * @return string HTML-скрытое поле
     */
    public function getTokenField(string $formName): string
    {
        $token = $this->generateToken($formName);
        return '<input type="hidden" name="csrf_token" value="' . $this->escape($token) . '">';
    }

    /**
     * Получить мета-тег с CSRF-токеном для AJAX
     * 
     * @param string $formName Имя формы
     * @return string HTML-мета тег
     */
    public function getMetaTag(string $formName = 'default'): string
    {
        $token = $this->generateToken($formName);
        return '<meta name="csrf-token" content="' . $this->escape($token) . '">';
    }

    /**
     * Очистить все просроченные токены
     * 
     * @return int Количество удалённых токенов
     */
    public function cleanExpiredTokens(): int
    {
        $tokens = $this->getTokens();
        $count = 0;

        foreach ($tokens as $formName => $tokenData) {
            if (!$this->isTokenValid($tokenData)) {
                unset($tokens[$formName]);
                $count++;
            }
        }

        $this->saveTokens($tokens);

        return $count;
    }

    /**
     * Проверить, валиден ли токен
     * 
     * @param array $tokenData Данные токена
     * @return bool
     */
    protected function isTokenValid(array $tokenData): bool
    {
        return isset($tokenData['expires_at']) && $tokenData['expires_at'] > time();
    }

    /**
     * Сгенерировать случайный токен
     * 
     * @return string
     */
    protected function generateRandomToken(): string
    {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes(32));
        }
        
        return md5(uniqid((string)mt_rand(), true));
    }

    /**
     * Получить все токены из сессии
     * 
     * @return array
     */
    protected function getTokens(): array
    {
        return $this->session->get(self::SESSION_KEY, []);
    }

    /**
     * Сохранить токены в сессию
     * 
     * @param array $tokens
     * @return void
     */
    protected function saveTokens(array $tokens): void
    {
        $this->session->set(self::SESSION_KEY, $tokens);
    }

}
