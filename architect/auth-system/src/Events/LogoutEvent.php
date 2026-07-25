<?php

namespace Architect\AuthSystem\Events;

/**
 * Событие выхода пользователя.
 */
class LogoutEvent extends AuthEvent
{
    public const NAME = 'auth.logout';

    /**
     * @param array $userData Данные пользователя
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
