<?php

namespace Architect\AuthSystem\Events;

/**
 * Событие регистрации нового пользователя.
 */
class RegisterEvent extends AuthEvent
{
    public const NAME = 'auth.register';

    /**
     * @param array $userData Данные зарегистрированного пользователя
     * @param string $guard Имя гварда
     */
    public function __construct(array $userData, string $guard = 'web')
    {
        parent::__construct(self::NAME, [
            'user' => $userData,
            'guard' => $guard,
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
}