<?php

declare(strict_types=1);

/**
 * Real-world example of using bundles in an Architect Framework application.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Define ROOT_DIR constant
define('ROOT_DIR', dirname(__DIR__) . '/');

echo "=== Real-world Bundle Usage Example ===\n\n";

// Simulate a web application bootstrap
echo "1. Bootstrapping application...\n";

// Create container and framework (as would be done in bootstrap.php)
$container = new \Architect\Core\Container();
$statement = new \Architect\Core\Statement($container);
$framework = new \Architect\Core\Framework($container, $statement);

echo "2. Creating and registering bundles...\n";

// Create UserBundle
$userBundle = new \App\Bundle\UserBundle\UserBundle();
$framework->registerBundle($userBundle);

// Create another example bundle
class BlogBundle extends \Architect\Support\AbstractBundle
{
    public function getName(): string { return 'BlogBundle'; }
    
    public function register(\Architect\Core\Contracts\ContainerInterface $container): void
    {
        $container->singleton('blog.service', function() {
            return new class {
                private array $posts = [];
                public function __construct() {
                    $this->posts = [
                        ['id' => 1, 'title' => 'First Post', 'content' => 'Hello World!'],
                        ['id' => 2, 'title' => 'Second Post', 'content' => 'Another post'],
                    ];
                }
                public function getPosts(): array { return $this->posts; }
                public function getPost(int $id): ?array { 
                    return $this->posts[$id] ?? null; 
                }
            };
        });
    }
}

$blogBundle = new BlogBundle();
$framework->registerBundle($blogBundle);

echo "   Registered bundles:\n";
foreach ($framework->getBundleManager()->getBundles() as $name => $bundle) {
    echo "   - $name\n";
}

echo "\n3. Registering bundle services...\n";
$framework->registerBundleServices();

echo "4. Booting bundles...\n";
$framework->bootBundles();

echo "\n5. Using bundle services in application...\n";

// Example 1: Using UserBundle services
echo "   Example 1: User Management\n";
if ($container->has('user.service')) {
    $userService = $container->get('user.service');
    
    // Get all users
    $users = $userService->getAll();
    echo "   Total users: " . count($users) . "\n";
    
    // Get a specific user
    $user = $userService->getById(1);
    if ($user) {
        echo "   User #1: " . $user['name'] . " (" . $user['email'] . ")\n";
    }
    
    // Create a new user
    $newUser = $userService->create([
        'name' => 'New User',
        'email' => 'new@example.com',
        'role' => 'user'
    ]);
    echo "   Created new user: " . $newUser['name'] . " (ID: " . $newUser['id'] . ")\n";
    
    // Search users
    $searchResults = $userService->search('john');
    echo "   Search for 'john': " . count($searchResults) . " results\n";
}

// Example 2: Using BlogBundle services
echo "\n   Example 2: Blog Management\n";
if ($container->has('blog.service')) {
    $blogService = $container->get('blog.service');
    
    $posts = $blogService->getPosts();
    echo "   Total blog posts: " . count($posts) . "\n";
    
    foreach ($posts as $post) {
        echo "   - Post #" . $post['id'] . ": " . $post['title'] . "\n";
    }
}

// Example 3: Using bundle aliases
echo "\n   Example 3: Using service aliases\n";
if ($container->has('user.service')) {
    // The bundle might have registered an alias
    // In UserBundle we registered: $container->alias('user', 'user.service');
    // But for this example, we'll use the direct service
    $userService = $container->get('user.service');
    $adminUsers = $userService->getByRole('admin');
    echo "   Admin users: " . count($adminUsers) . "\n";
}

// Example 4: Simulating HTTP request handling with bundle routes
echo "\n   Example 4: HTTP Request Simulation\n";
echo "   Available routes from UserBundle:\n";
echo "   - GET  /users           (user.index)\n";
echo "   - GET  /users/{id}      (user.show)\n";
echo "   - GET  /users/create    (user.create)\n";
echo "   - POST /users/create    (user.create)\n";
echo "   - GET  /users/{id}/edit (user.edit)\n";
echo "   - POST /users/{id}/edit (user.edit)\n";

// Example 5: Bundle configuration
echo "\n   Example 5: Bundle Configuration\n";
$configLoader = new \Architect\Core\Bundle\Config\BundleConfigLoader();
try {
    $config = $configLoader->load($userBundle, $container);
    if (!empty($config)) {
        echo "   UserBundle configuration loaded successfully\n";
    } else {
        echo "   No configuration file found for UserBundle (expected)\n";
    }
} catch (Exception $e) {
    echo "   Configuration error: " . $e->getMessage() . "\n";
}

// Example 6: Bundle commands (CLI)
echo "\n   Example 6: Console Commands\n";
echo "   Available commands from UserBundle:\n";
echo "   - command.CreateUserCommand\n";
echo "   - command.ListUsersCommand\n";
echo "   - command.UpdateUserCommand\n";

// Example 7: Bundle views
echo "\n   Example 7: View Templates\n";
$viewLoader = new \Architect\Core\Bundle\View\BundleViewLoader();
$viewDirs = $viewLoader->getViewDirectories($userBundle);
if (!empty($viewDirs)) {
    echo "   View directories found: " . count($viewDirs) . "\n";
    foreach ($viewDirs as $dir) {
        echo "   - " . $dir . "\n";
    }
} else {
    echo "   No view directories found (UserBundle doesn't have views in this example)\n";
}

echo "\n=== Application Structure with Bundles ===\n";
echo "With the bundle system, your application can be organized as:\n";
echo "\nproject/\n";
echo "├── src/\n";
echo "│   ├── Bundle/\n";
echo "│   │   ├── UserBundle/          # User management\n";
echo "│   │   │   ├── UserBundle.php\n";
echo "│   │   │   ├── Service/         # Business logic\n";
echo "│   │   │   ├── Repository/      # Data access\n";
echo "│   │   │   ├── Controller/      # HTTP controllers\n";
echo "│   │   │   ├── Resources/       # Assets, views, config\n";
echo "│   │   │   └── Command/         # CLI commands\n";
echo "│   │   ├── BlogBundle/          # Blog functionality\n";
echo "│   │   └── ApiBundle/           # API endpoints\n";
echo "├── app/                         # Application code\n";
echo "└── public/                      # Web root\n";

echo "\n=== Benefits of Using Bundles ===\n";
echo "1. Modularity: Each feature is in its own bundle\n";
echo "2. Reusability: Bundles can be shared between projects\n";
echo "3. Maintainability: Clear separation of concerns\n";
echo "4. Testability: Bundles can be tested independently\n";
echo "5. Scalability: Easy to add/remove features\n";

echo "\n=== How to Use in Your Project ===\n";
echo "1. Create your bundle classes extending AbstractBundle\n";
echo "2. Register services in the register() method\n";
echo "3. Add routes, views, commands as needed\n";
echo "4. Register bundles in your bootstrap:\n";
echo "   \$framework->registerBundle(new YourBundle());\n";
echo "   \$framework->registerBundleServices();\n";
echo "   \$framework->bootBundles();\n";
echo "5. Use bundle services via the container\n";

echo "\nThe bundle system is now fully functional and ready for production use!\n";