<?php

namespace Architect\AuthSystem\Events;

/**
 * Событие успешного входа пользователя.
 */
class LoginEvent extends AuthEvent
{
    public const NAME = 'auth.login';

    /**
     * @param array $userData Данные пользователя
     * @param string $guard Имя гварда (например, 'web', 'api')
     * @param string $method Метод аутентификации (например, 'password', 'oauth')
     */
    public function __construct(array $userData, string $guard = 'web', string $method = 'password')
    {
        parent::__construct(self::NAME, [
            'user' => $userData,
            'guard' => $guard,
            'method' => $method,
        ]);
    }

    /**
     * Получить данные пользователя.
     *
     * @return array
     */
    public function getUser(): array
    {
        return $this->payload['user'];
    }

    /**
     * Получить гвард.
     *
     * @return string
     */
    public function getGuard(): string
    {
        return $this->payload['guard'];
    }

    /**
     * Получить метод аутентификации.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->payload['method'];
    }
}