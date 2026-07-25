<?php

namespace Architect\AuthSystem\Events;

/**
 * Событие отказа в доступе (недостаточно прав).
 */
class PermissionDeniedEvent extends AuthEvent
{
    public const NAME = 'auth.permission_denied';

    /**
     * @param array $userData Данные пользователя (может быть null, если не аутентифицирован)
     * @param string $permission Требуемое разрешение
     * @param string $resource Ресурс
     * @param string $action Действие
     */
    public function __construct(?array $userData, string $permission, string $resource = '', string $action = '')
    {
        parent::__construct(self::NAME, [
            'user' => $userData,
            'permission' => $permission,
            'resource' => $resource,
            'action' => $action,
        ]);
    }

    /**
     * Получить данные пользователя.
     *
     * @return array|null
     */
    public function getUser(): ?array
    {
        return $this->payload['user'];
    }

    /**
     * Получить требуемое разрешение.
     *
     * @return string
     */
    public function getPermission(): string
    {
        return $this->payload['permission'];
    }

    /**
     * Получить ресурс.
     *
     * @return string
     */
    public function getResource(): string
    {
        return $this->payload['resource'];
    }

    /**
     * Получить действие.
     *
     * @return string
     */
    public function getAction(): string
    {
        return $this->payload['action'];
    }
}
