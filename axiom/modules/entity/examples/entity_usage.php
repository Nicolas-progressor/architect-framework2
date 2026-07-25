<?php

declare(strict_types=1);

namespace Examples;

use App\Entity\User;
use App\Entity\Order;
use Axiom\Entity\EntityManager;
use Axiom\Entity\Repository;
use Axiom\Orm\Orm;
use Axiom\Orm\Connection\ConnectionManager;

// Load configuration
ConnectionManager::loadConfig(__DIR__ . '/config/database.json');

// ========== BASIC ENTITY OPERATIONS ==========

// Get repository
$userRepository = User::repository();

// Find all users
$users = User::all();

// Find by ID
$user = User::find(1);

// Find by criteria
$admins = User::findBy(['role' => 'admin']);
$activeUser = User::findOneBy(['email' => 'john@example.com', 'status' => 'active']);

// ========== CREATE ENTITY ==========

$user = new User();
$user->setName('John Doe');
$user->setEmail('john@example.com');
$user->setPassword(password_hash('password123', PASSWORD_DEFAULT));
$user->setStatus('active');
$user->setRole('user');
$user->setCreatedAt(new \DateTime());

// Save (insert or update)
$userId = $user->save();

// Or use repository
$newUser = $userRepository->create([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
    'status' => 'active'
]);
$newUserId = $newUser->save();

// ========== UPDATE ENTITY ==========

$user = User::find(1);
$user->setName('Updated Name');
$user->save();

// ========== DELETE ENTITY ==========

$user = User::find(1);
$user->delete();

// Or delete by ID
User::repository()->deleteById(1);

// ========== PAGINATION ==========

$page = User::repository()->paginate(page: 1, perPage: 15);
echo "Total users: " . $page['total'] . "\n";
echo "Page: " . $page['page'] . " of " . $page['last_page'] . "\n";

foreach ($page['data'] as $user) {
    echo $user->getName() . "\n";
}

// ========== USING ENTITY WITH QUERY BUILDER ==========

// Get users as entities
$users = Orm::table('users')
    ->select(['id', 'name', 'email', 'status'])
    ->where('status', '=', 'active')
    ->entity(User::class)
    ->get();

foreach ($users as $user) {
    echo $user->getName() . " - " . $user->getEmail() . "\n";
}

// ========== ENTITY METADATA ==========

$metadata = EntityManager::getMetadata(User::class);
echo "Table: " . $metadata->table . "\n";
echo "Primary Key: " . $metadata->getPrimaryKey() . "\n";

foreach ($metadata->columns as $column) {
    echo "Column: {$column->column} ({$column->type})\n";
}

// ========== CUSTOM REPOSITORY ==========

class UserRepository extends Repository
{
    /**
     * Find active users
     */
    public function findActive(): array
    {
        return $this->findBy(['status' => 'active']);
    }

    /**
     * Find users by role
     */
    public function findByRole(string $role): array
    {
        return $this->findBy(['role' => $role]);
    }

    /**
     * Search users by name
     */
    public function search(string $query): array
    {
        return $this->query()
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->entity(User::class)
            ->get();
    }
}

// Use custom repository
$userRepo = new UserRepository(User::class);
$activeUsers = $userRepo->findActive();
$admins = $userRepo->findByRole('admin');
$searchResults = $userRepo->search('john');

// ========== TRANSACTIONS ==========

Orm::transaction(function () {
    // Create user
    $user = new User();
    $user->setName('Test User');
    $user->setEmail('test@example.com');
    $user->save();

    // Create order for user
    $order = new Order();
    $order->setUserId($user->getId());
    $order->setOrderNumber('ORD-' . time());
    $order->setTotal(99.99);
    $order->save();
});
