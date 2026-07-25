<?php

declare(strict_types=1);

namespace app\home\modules\home\model;

use Architect\Services\Mvc\ModelBase;
use Axiom\Orm\Integrations\Architect\ModelOrmTrait;

/**
 * Example model using Axiom ORM
 */
class HomeModel extends ModelBase
{
    use ModelOrmTrait;

    /**
     * Get all active users
     */
    public function getActiveUsers(): array
    {
        return $this->db()
            ->select(['id', 'name', 'email'])
            ->from('users')
            ->where('status', '=', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get user by ID
     */
    public function getUserById(int $id): ?array
    {
        return $this->db()
            ->from('users')
            ->where('id', '=', $id)
            ->first();
    }

    /**
     * Create new user
     */
    public function createUser(string $name, string $email): int
    {
        return $this->db()
            ->insert('users')
            ->set([
                'name' => $name,
                'email' => $email,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ])
            ->execute();
    }

    /**
     * Update user
     */
    public function updateUser(int $id, array $data): int
    {
        return $this->db()
            ->update('users')
            ->set($data)
            ->where('id', '=', $id)
            ->execute();
    }

    /**
     * Delete user
     */
    public function deleteUser(int $id): int
    {
        return $this->db()
            ->delete('users')
            ->where('id', '=', $id)
            ->execute();
    }

    /**
     * Get users with orders (JOIN example)
     */
    public function getUsersWithOrders(): array
    {
        return $this->db()
            ->select([
                'users.id',
                'users.name',
                'COUNT(orders.id) as order_count',
                'SUM(orders.total) as total_spent'
            ])
            ->from('users')
            ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
            ->where('users.status', '=', 'active')
            ->groupBy('users.id')
            ->having('order_count', '>', 0)
            ->orderBy('total_spent', 'DESC')
            ->get();
    }

    /**
     * Search users
     */
    public function searchUsers(string $query): array
    {
        $search = "%{$query}%";
        
        return $this->db()
            ->from('users')
            ->where('name', 'LIKE', $search)
            ->orWhere('email', 'LIKE', $search)
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_users' => $this->db()->from('users')->count(),
            'active_users' => $this->db()->from('users')->where('status', '=', 'active')->count(),
            'total_orders' => $this->db()->from('orders')->count(),
            'total_revenue' => $this->db()->from('orders')->sum('total'),
        ];
    }

    /**
     * Transfer money (transaction example)
     */
    public function transferMoney(int $fromId, int $toId, float $amount): bool
    {
        try {
            return $this->transaction(function () use ($fromId, $toId, $amount) {
                // Deduct from sender
                $this->db()
                    ->raw("UPDATE accounts SET balance = balance - ? WHERE id = ?", [$amount, $fromId])
                    ->execute();

                // Add to receiver
                $this->db()
                    ->raw("UPDATE accounts SET balance = balance + ? WHERE id = ?", [$amount, $toId])
                    ->execute();

                return true;
            });
        } catch (\Throwable $e) {
            return false;
        }
    }
}
