<?php

declare(strict_types=1);

namespace Architect\Auth\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use InvalidArgumentException;

class JwtTokenService
{
    private string $secret;
    private int $ttl;
    private string $algorithm = 'HS256';

    public function __construct(string $secret, int $ttl = 3600)
    {
        $this->secret = $secret;
        $this->ttl = $ttl;
    }

    /**
     * Создать JWT токен для пользователя.
     *
     * @param array $payload Дополнительные данные
     * @return string
     */
    public function encode(array $payload): string
    {
        $defaultPayload = [
            'iat' => time(),
            'exp' => time() + $this->ttl,
            'iss' => 'architect-auth',
        ];

        $payload = array_merge($defaultPayload, $payload);

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Декодировать и верифицировать JWT токен.
     *
     * @param string $token
     * @return array|null Декодированный payload или null при ошибке
     */
    public function decode(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            // Токен просрочен
            return null;
        } catch (SignatureInvalidException $e) {
            // Неверная подпись
            return null;
        } catch (InvalidArgumentException $e) {
            // Неверный аргумент
            return null;
        } catch (\Exception $e) {
            // Любая другая ошибка
            return null;
        }
    }

    /**
     * Проверить, действителен ли токен.
     */
    public function validate(string $token): bool
    {
        return $this->decode($token) !== null;
    }

    /**
     * Получить payload без проверки подписи (небезопасно, только для отладки).
     */
    public function decodeUnverified(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = base64_decode(strtr($parts[1], '-_', '+/'));
        if ($payload === false) {
            return null;
        }

        return json_decode($payload, true);
    }

    /**
     * Обновить токен (выдать новый с тем же payload, но новым сроком).
     */
    public function refresh(string $token): ?string
    {
        $payload = $this->decode($token);
        if (!$payload) {
            return null;
        }

        // Удаляем служебные поля
        unset($payload['iat'], $payload['exp'], $payload['nbf'], $payload['jti']);

        return $this->encode($payload);
    }
}