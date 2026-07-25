<?php

declare(strict_types=1);

namespace Architect\AuthSystem\Services;

use Architect\AuthSystem\Contracts\AuthorizationInterface;
use Architect\AuthSystem\Events\EventDispatcherInterface;
use Architect\AuthSystem\Events\PermissionDeniedEvent;
use Architect\AuthSystem\Models\Role;
use Architect\AuthSystem\Models\User;
use Architect\Core\Container;

class AuthorizationService implements AuthorizationInterface
{
    private array $config = [];

    public function __construct(
        private ?EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->loadConfig();
    }

    /**
     * Загрузить конфигурацию ролей и разрешений.
     */
    private function loadConfig(): void
    {
        try {
            $container = Container::getInstance();
            if ($container->has('config')) {
                $config = $container->get('config');
                $this->config = $config->get('auth', []);
            }
        } catch (\Exception $e) {
            // Конфигурация не загружена
        }
    }

    public function hasPermission(User $user, string $permission): bool
    {
        // Админ имеет все разрешения
        if ($this->hasRole($user, 'admin')) {
            return true;
        }

        $role = $user->getRole();
        if (!$role) {
            $this->dispatchPermissionDenied($user, $permission);
            return false;
        }

        $has = $role->hasPermission($permission);
        if (!$has) {
            $this->dispatchPermissionDenied($user, $permission);
        }
        return $has;
    }

    public function hasRole(User $user, string $role): bool
    {
        $userRole = $user->getRole();
        if (!$userRole) {
            return false;
        }

        return $userRole->getName() === $role;
    }

    public function assignRole(User $user, string $role): bool
    {
        $roleModel = Role::findByName($role);
        if (!$roleModel) {
            // Попробуем создать роль из конфига
            $roleModel = $this->createRoleFromConfig($role);
            if (!$roleModel) {
                return false;
            }
        }

        $user->setRole($roleModel);
        return $user->save();
    }

    public function revokeRole(User $user, string $role): bool
    {
        if (!$this->hasRole($user, $role)) {
            return false;
        }

        $defaultRole = $this->config['default_role'] ?? 'guest';
        return $this->assignRole($user, $defaultRole);
    }

    public function getRoles(User $user): array
    {
        $role = $user->getRole();
        return $role ? [$role->getName()] : [];
    }

    public function getPermissions(User $user): array
    {
        $role = $user->getRole();
        if (!$role) {
            return [];
        }

        return $role->getPermissions();
    }

    /**
     * Создать объект Role на основе конфигурации.
     */
    private function createRoleFromConfig(string $roleName): ?Role
    {
        if (!isset($this->config['roles'][$roleName])) {
            return null;
        }

        $roleData = $this->config['roles'][$roleName];
        $role = new Role();
        $role->setName($roleName);
        $role->setDescription($roleData['description'] ?? null);
        $role->setPermissions($roleData['permissions'] ?? []);

        if ($role->save()) {
            return $role;
        }

        return null;
    }

    /**
     * Получить все доступные роли из конфигурации.
     */
    public function getAvailableRoles(): array
    {
        return array_keys($this->config['roles'] ?? []);
    }

    /**
     * Получить все доступные разрешения из конфигурации.
     */
    public function getAvailablePermissions(): array
    {
        return $this->config['permissions'] ?? [];
    }

    /**
     * Диспетчеризация события отказа в доступе.
     */
    private function dispatchPermissionDenied(User $user, string $permission): void
    {
        if (!$this->eventDispatcher) {
            return;
        }

        $event = new PermissionDeniedEvent(
            $user->toArray(),
            $permission,
            '',
            ''
        );
        $this->eventDispatcher->dispatch(PermissionDeniedEvent::NAME, $event);
    }
}
