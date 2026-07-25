<?php

declare(strict_types=1);

namespace Architect\Auth\Models;

use Architect\Services\Mvc\ModelBase;

/**
 * Permission Model
 * 
 * Модель разрешения для системы RBAC.
 * 
 * @package Architect\Auth\Models
 */
class Permission extends ModelBase
{
    /**
     * Таблица разрешений
     */
    protected string $table = 'auth_permissions';

    /**
     * Первичный ключ
     */
    protected string $primaryKey = 'id';

    /**
     * Имя разрешения
     */
    protected string $name;

    /**
     * Описание
     */
    protected ?string $description = null;

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
     * Получить имя
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Установить имя
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
     * Найти по имени
     * 
     * @param string $name
     * @return static|null
     */
    public static function findByName(string $name): ?static
    {
        $instance = new static();
        
        try {
            return $instance->where('name', '=', $name)->first();
        } catch (\Exception $e) {
            // Если нет таблицы - пробуем из конфига
            $config = $instance->getAuthConfig();
            if (isset($config['permissions'][$name])) {
                $instance->name = $name;
                $instance->description = $config['permissions'][$name];
                return $instance;
            }
        }

        return null;
    }

    /**
     * Получить все доступные разрешения из конфига
     * 
     * @return array
     */
    public static function allFromConfig(): array
    {
        $instance = new static();
        $config = $instance->getAuthConfig();
        
        $permissions = [];
        
        if (isset($config['permissions'])) {
            foreach ($config['permissions'] as $name => $description) {
                $perm = new static();
                $perm->name = $name;
                $perm->description = $description;
                $permissions[] = $perm;
            }
        }

        return $permissions;
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
     * Сохранить разрешение
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

        return (bool)$this->insert($data);
    }
}
