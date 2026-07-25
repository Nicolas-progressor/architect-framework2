<?php

declare(strict_types=1);

namespace Examples;

use Axiom\Orm\Orm;
use Axiom\Orm\Connection\ConnectionManager;

// Load configuration
ConnectionManager::loadConfig(__DIR__ . '/config/database.json');

// ========== BASIC SELECT ==========

// Get all users
$users = Orm::table('users')->get();

// Get active users with limit
$activeUsers = Orm::table('users')
    ->select(['id', 'name', 'email'])
    ->where('status', '=', 'active')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();

// Get single user
$user = Orm::table('users')
    ->where('id', '=', 1)
    ->first();

// Get user emails as array
$emails = Orm::table('users')
    ->pluck('email');

// ========== INSERT ==========

$userId = Orm::table('users')
    ->insert('users')
    ->set([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ])
    ->execute();

// ========== UPDATE ==========

$affected = Orm::table('users')
    ->update('users')
    ->set(['status' => 'inactive'])
    ->where('id', '=', 1)
    ->execute();

// ========== DELETE ==========

$deleted = Orm::table('users')
    ->delete('users')
    ->where('status', '=', 'banned')
    ->execute();

// ========== COMPLEX QUERIES ==========

$report = Orm::raw("
    SELECT u.name, COUNT(o.id) as order_count, SUM(o.total) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.status = 'active'
    GROUP BY u.id
    HAVING total_spent > 1000
    ORDER BY total_spent DESC
", [$startDate, $endDate])->get();

// ========== AGGREGATES ==========

$userCount = Orm::table('users')->count();
$activeCount = Orm::table('users')->where('status', '=', 'active')->count();
$totalSales = Orm::table('orders')->sum('total');
$avgPrice = Orm::table('products')->avg('price');

// ========== TRANSACTIONS ==========

Orm::transaction(function () {
    Orm::table('accounts')
        ->update('accounts')
        ->set(['balance' => 100])
        ->where('id', '=', 1)
        ->execute();

    Orm::table('accounts')
        ->update('accounts')
        ->set(['balance' => 50])
        ->where('id', '=', 2)
        ->execute();
});

// ========== WITH Entity ==========

// Define entity class
class UserEntity
{
    public int $id;
    public string $name;
    public string $email;
    public string $status;
    public string $createdAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}

// Get users as entities
$entities = Orm::table('users')
    ->select(['id', 'name', 'email', 'status', 'created_at'])
    ->where('status', '=', 'active')
    ->entity(UserEntity::class)
    ->get();

foreach ($entities as $user) {
    echo $user->getName();
}

// ========== JOIN QUERIES ==========

$orders = Orm::table('orders')
    ->select(['orders.id', 'orders.total', 'users.name as user_name'])
    ->join('users', 'orders.user_id', '=', 'users.id')
    ->where('orders.status', '=', 'completed')
    ->orderBy('orders.created_at', 'DESC')
    ->get();

// ========== WHERE CONDITIONS ==========

// WHERE IN
$users = Orm::table('users')
    ->whereIn('role', ['admin', 'moderator'])
    ->get();

// WHERE BETWEEN
$users = Orm::table('users')
    ->whereBetween('age', [18, 65])
    ->get();

// WHERE NULL
$users = Orm::table('users')
    ->whereNull('deleted_at')
    ->get();

// Multiple OR conditions
$users = Orm::table('users')
    ->where('status', '=', 'active')
    ->orWhere('role', '=', 'admin')
    ->get();
