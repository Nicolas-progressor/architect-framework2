<?php

namespace Architect\AuthSystem\Events;

/**
 * Событие неудачной попытки аутентификации.
 */
class FailedAuthenticationEvent extends AuthEvent
{
    public const NAME = 'auth.failed';

    /**
     * @param string $identifier Идентификатор (email, username)
     * @param string $reason Причина (например, 'wrong_password', 'user_not_found')
     * @param string $guard Имя гварда
     * @param array $context Дополнительный контекст
     */
    public function __construct(string $identifier, string $reason, string $guard = 'web', array $context = [])
    {
        parent::__construct(self::NAME, [
            'identifier' => $identifier,
            'reason' => $reason,
            'guard' => $guard,
            'context' => $context,
        ]);
    }

    /**
     * Получить идентификатор.
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->payload['identifier'];
    }

    /**
     * Получить причину.
     *
     * @return string
     */
    public function getReason(): string
    {
        return $this->payload['reason'];
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
     * Получить контекст.
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->payload['context'];
    }
}