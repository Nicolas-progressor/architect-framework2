<?php

declare(strict_types=1);

namespace App\Bundle\UserBundle\Service;

/**
 * User service for managing users.
 */
class UserService
{
    /** @var array Simulated user storage */
    private array $users = [];
    
    /** @var bool */
    private bool $initialized = false;

    /**
     * Initialize the service.
     */
    public function initialize(): void
    {
        $this->users = [
            1 => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'role' => 'user'],
            2 => ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'role' => 'admin'],
            3 => ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com', 'role' => 'user'],
        ];
        
        $this->initialized = true;
    }

    /**
     * Get all users.
     */
    public function getAll(): array
    {
        return array_values($this->users);
    }

    /**
     * Get user by ID.
     */
    public function getById(int $id): ?array
    {
        return $this->users[$id] ?? null;
    }

    /**
     * Create a new user.
     */
    public function create(array $data): array
    {
        $id = max(array_keys($this->users)) + 1;
        $user = array_merge(['id' => $id], $data);
        $this->users[$id] = $user;
        
        return $user;
    }

    /**
     * Update a user.
     */
    public function update(int $id, array $data): ?array
    {
        if (!isset($this->users[$id])) {
            return null;
        }
        
        $this->users[$id] = array_merge($this->users[$id], $data);
        
        return $this->users[$id];
    }

    /**
     * Delete a user.
     */
    public function delete(int $id): bool
    {
        if (!isset($this->users[$id])) {
            return false;
        }
        
        unset($this->users[$id]);
        return true;
    }

    /**
     * Search users by name or email.
     */
    public function search(string $query): array
    {
        $results = [];
        foreach ($this->users as $user) {
            if (stripos($user['name'], $query) !== false || 
                stripos($user['email'], $query) !== false) {
                $results[] = $user;
            }
        }
        
        return $results;
    }

    /**
     * Get users by role.
     */
    public function getByRole(string $role): array
    {
        $results = [];
        foreach ($this->users as $user) {
            if ($user['role'] === $role) {
                $results[] = $user;
            }
        }
        
        return $results;
    }

    /**
     * Check if service is initialized.
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Get user count.
     */
    public function count(): int
    {
        return count($this->users);
    }

    /**
     * Cleanup service resources.
     */
    public function cleanup(): void
    {
        $this->users = [];
        $this->initialized = false;
    }
}