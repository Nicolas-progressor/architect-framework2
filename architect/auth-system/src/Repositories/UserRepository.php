<?php

declare(strict_types=1);

namespace Architect\AuthSystem\Repositories;

use Architect\AuthSystem\Contracts\UserProviderInterface;
use Architect\AuthSystem\Models\Role;
use Architect\AuthSystem\Models\User;

class UserRepository implements UserProviderInterface
{
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findByUsername(string $username): ?User
    {
        return User::findByUsername($username);
    }

    public function findByEmail(string $email): ?User
    {
        return User::findByEmail($email);
    }

    public function findByOAuthId(string $provider, string $oauthId): ?User
    {
        // Предполагаем, что в модели User есть метод findByOAuthId
        // Если нет, можно реализовать через запрос к таблице user_oauth
        return User::findByOAuthId($provider, $oauthId);
    }

    public function create(array $data): ?User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): bool
    {
        // Обновляем поля
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

        return $user->save();
    }

    public function delete(User $user): bool
    {
        // В ModelBase есть метод delete?
        // Предполагаем, что есть
        return $user->delete();
    }

    public function usernameExists(string $username): bool
    {
        return $this->findByUsername($username) !== null;
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function getDefaultRole(): Role
    {
        $role = Role::findByName('guest');
        if (!$role) {
            // Создать роль гостя по умолчанию
            $role = new Role();
            $role->setName('guest');
            $role->setDescription('Гость');
            $role->setPermissions(['view_public_content']);
            $role->save();
        }
        return $role;
    }
}
