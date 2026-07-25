<?php

declare(strict_types=1);

namespace Architect\Auth\Models;

use Architect\Services\Mvc\ModelBase;

/**
 * Role Model
 *
 * Модель роли для системы RBAC.
 *
 * @package Architect\Auth\Models
 */
class Role extends ModelBase
{
    /**
     * Таблица ролей
     */
    protected string $table = 'auth_roles';

    /**
     * Первичный ключ
     */
    protected string $primaryKey = 'id';

    /**
     * Имя роли
     */
    protected string $name;

    /**
     * Описание роли
     */
    protected ?string $description = null;

    /**
     * Разрешения роли (кэш)
     */
    protected ?array $permissions = null;

    /**
     * Конструктор
     *
     * @param int|null $id
     */
    public function __construct(?int $id = null)
    {
        parent::__construct();

        if ($id !== null) {
            $this->load($id);
        }
    }

    /**
     * Получить имя роли
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Установить имя роли
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Получить описание
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Установить описание
     *
     * @param string|null $description
     * @return self
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Проверить, имеет ли роль разрешение
     *
     * @param string $permissionName
     * @return bool
     */
    public function hasPermission(string $permissionName): bool
    {
        // Админ имеет все разрешения
        if ($this->name === 'admin') {
            return true;
        }

        $permissions = $this->getPermissions();

        // Разрешение "*" означает все разрешения
        if (in_array('*', $permissions, true)) {
            return true;
        }

        return in_array($permissionName, $permissions, true);
    }

    /**
     * Получить все разрешения роли
     *
     * @return array
     */
    public function getPermissions(): array
    {
        if ($this->permissions !== null) {
            return $this->permissions;
        }

        // Пробуем загрузить из БД
        $this->permissions = [];

        // Пробуем через config
        $config = $this->getAuthConfig();

        if (isset($config['roles'][$this->name]['permissions'])) {
            $this->permissions = $config['roles'][$this->name]['permissions'];
        }

        return $this->permissions;
    }

    /**
     * Установить разрешения (кэш)
     *
     * @param array $permissions
     * @return self
     */
    public function setPermissions(array $permissions): self
    {
        $this->permissions = $permissions;
        return $this;
    }

    /**
     * Найти роль по имени
     *
     * @param string $name
     * @return static|null
     */
    public static function findByName(string $name): ?static
    {
        $instance = new static();

        // Пробуем найти в БД
        try {
            $result = $instance->where('name', '=', $name)->first();
            if ($result) {
                return $result;
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки БД
        }

        // Пробуем загрузить из конфига
        $config = $instance->getAuthConfig();

        if (isset($config['roles'][$name])) {
            $instance->name = $name;
            $instance->description = $config['roles'][$name]['description'] ?? null;
            $instance->permissions = $config['roles'][$name]['permissions'] ?? [];
            return $instance;
        }

        return null;
    }

    /**
     * Получить конфигурацию auth
     *
     * @return array
     */
    protected function getAuthConfig(): array
    {
        try {
            $container = \Architect\Core\Container::getInstance();
            if ($container->has('config')) {
                $config = $container->get('config');
                return $config->get('auth', []);
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return [];
    }

    /**
     * Сохранить роль
     *
     * @return bool
     */
    public function save(): bool
    {
        $data = [
            'name' => $this->name,
            'description' => $this->description,
        ];

        if ($this->id) {
            return $this->update($data);
        }

        return (bool) $this->insert($data);
    }

    /**
     * Удалить роль
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->id) {
            return false;
        }

        return parent::delete();
    }
}
