<?php

declare(strict_types=1);

namespace app\admin\modules\users\model;

use Architect\Services\Mvc\ModelBase;

class users extends ModelBase
{
    private array $users = [];

    public function getAll(): array
    {
        if ($this->container->has('user.service')) {
            $userService = $this->container->get('user.service');
            return $userService->getAll();
        }
        return array_values($this->users);
    }

    public function getById(int $id): ?array
    {
        if ($this->container->has('user.service')) {
            $userService = $this->container->get('user.service');
            return $userService->getById($id);
        }
        return $this->users[$id] ?? null;
    }

    public function create(array $data): array
    {
        if ($this->container->has('user.service')) {
            $userService = $this->container->get('user.service');
            return $userService->create($data);
        }
        $id = count($this->users) + 1;
        $user = array_merge(['id' => $id], $data);
        $this->users[$id] = $user;
        return $user;
    }

    public function update(int $id, array $data): ?array
    {
        if ($this->container->has('user.service')) {
            $userService = $this->container->get('user.service');
            return $userService->update($id, $data);
        }
        if (!isset($this->users[$id])) {
            return null;
        }
        $this->users[$id] = array_merge($this->users[$id], $data);
        return $this->users[$id];
    }

    public function delete(int $id): bool
    {
        if ($this->container->has('user.service')) {
            $userService = $this->container->get('user.service');
            return $userService->delete($id);
        }
        if (!isset($this->users[$id])) {
            return false;
        }
        unset($this->users[$id]);
        return true;
    }
}
