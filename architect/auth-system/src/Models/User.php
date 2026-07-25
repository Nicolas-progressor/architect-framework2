<?php

declare(strict_types=1);

namespace Architect\AuthSystem\Models;

use Architect\Services\Mvc\ModelBase;
use Architect\AuthSystem\Models\Role;

/**
 * User Model
 * 
 * Модель пользователя для системы авторизации.
 * 
 * @package Architect\AuthSystem\Models
 */
class User extends ModelBase
{
    /**
     * Таблица пользователей
     */
    protected string $table = 'auth_users';

    /**
     * Первичный ключ
     */
    protected string $primaryKey = 'id';

    /**
     * Имя пользователя
     */
    protected string $username;

    /**
     * Email
     */
    protected string $email;

    /**
     * Хэш пароля
     */
    protected string $password;

    /**
     * ID роли
     */
    protected ?int $roleId = null;

    /**
     * Роль (кэш)
     */
    protected ?Role $role = null;

    /**
     * Время создания
     */
    protected ?string $createdAt = null;

    /**
     * Время обновления
     */
    protected ?string $updatedAt = null;

    /**
     * Атрибуты для скрытия
     */
    protected array $hidden = ['password'];

    /**
     * Атрибуты для массового присваивания
     */
    protected array $fillable = ['username', 'email', 'password', 'role_id'];

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
     * Получить ID
     * 
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Получить имя пользователя
     * 
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Установить имя пользователя
     * 
     * @param string $username
     * @return self
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Получить email
     * 
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Установить email
     * 
     * @param string $email
     * @return self
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Получить пароль
     * 
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Установить пароль (хэширует)
     * 
     * @param string $password
     * @return self
     */
    public function setPassword(string $password): self
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        return $this;
    }

    /**
     * Проверить пароль
     * 
     * @param string $password
     * @return bool
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * Получить роль
     * 
     * @return Role|null
     */
    public function getRole(): ?Role
    {
        if ($this->role !== null) {
            return $this->role;
        }

        // Если есть roleId - загружаем из БД
        if ($this->roleId) {
            $this->role = new Role($this->roleId);
            return $this->role;
        }

        // Пробуем найти по имени роли из конфига
        $config = $this->getAuthConfig();
        $defaultRole = $config['default_role'] ?? 'guest';
        
        $this->role = Role::findByName($defaultRole);

        return $this->role;
    }

    /**
     * Установить роль
     * 
     * @param Role|string $role
     * @return self
     */
    public function setRole(Role|string $role): self
    {
        if (is_string($role)) {
            $role = Role::findByName($role);
        }

        if ($role instanceof Role) {
            $this->role = $role;
            $this->roleId = $role->id;
        }

        return $this;
    }

    /**
     * Проверить, имеет ли пользователь роль
     * 
     * @param string $roleName
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        $role = $this->getRole();
        
        if (!$role) {
            return false;
        }

        return $role->getName() === $roleName;
    }

    /**
     * Проверить, имеет ли пользователь разрешение
     * 
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        $role = $this->getRole();
        
        if (!$role) {
            return false;
        }

        return $role->hasPermission($permission);
    }

    /**
     * Проверить, является ли админом
     * 
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Найти по username
     * 
     * @param string $username
     * @return static|null
     */
    public static function findByUsername(string $username): ?static
    {
        $instance = new static();
        
        try {
            return $instance->where('username', '=', $username)->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Найти по email
     * 
     * @param string $email
     * @return static|null
     */
    public static function findByEmail(string $email): ?static
    {
        $instance = new static();
        
        try {
            return $instance->where('email', '=', $email)->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Найти по OAuth ID
     * 
     * @param string $provider
     * @param string $oauthId
     * @return static|null
     */
    public static function findByOAuthId(string $provider, string $oauthId): ?static
    {
        // Временная реализация: предполагаем, что есть таблица auth_user_oauth
        // Пока возвращаем null, нужно реализовать после миграций
        return null;
    }

    /**
     * Добавить OAuth ID для пользователя
     * 
     * @param string $provider
     * @param string $oauthId
     * @return bool
     */
    public function addOAuthId(string $provider, string $oauthId): bool
    {
        // Временная заглушка
        return true;
    }

    /**
     * Найти по ID
     * 
     * @param int $id
     * @return static|null
     */
    public static function find(int $id): ?static
    {
        $instance = new static();
        return $instance->load($id) ? $instance : null;
    }

    /**
     * Создать пользователя
     * 
     * @param array $data
     * @return static|null
     */
    public static function create(array $data): ?static
    {
        $user = new static();
        
        if (isset($data['username'])) {
            $user->setUsername($data['username']);
        }
        
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        
        if (isset($data['password'])) {
            $user->setPassword($data['password']);
        }
        
        if (isset($data['role'])) {
            $user->setRole($data['role']);
        }

        if ($user->save()) {
            return $user;
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
     * Сохранить пользователя
     * 
     * @return bool
     */
    public function save(): bool
    {
        $data = [
            'username' => $this->username,
            'email' => $this->email,
            'password' => $this->password,
            'role_id' => $this->roleId,
        ];

        if ($this->id) {
            return $this->update($data);
        }

        return (bool)$this->insert($data);
    }

    /**
     * Преобразование в массив
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->getRole()?->getName(),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * JSON сериализация
     * 
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
