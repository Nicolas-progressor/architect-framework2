<?php

declare(strict_types=1);

/**
 * Demonstration of the bundle system for Architect Framework.
 * 
 * This script shows how to use the bundle system components.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Define ROOT_DIR constant
define('ROOT_DIR', dirname(__DIR__) . '/');

echo "=== Architect Framework Bundle System Demo ===\n\n";

// 1. Create container and framework
echo "1. Creating container and framework...\n";
$container = new \Architect\Core\Container();
$statement = new \Architect\Core\Statement($container);
$framework = new \Architect\Core\Framework($container, $statement);

// 2. Create example bundle
echo "2. Creating example bundle...\n";
$exampleBundle = new \Examples\Bundle\ExampleBundle\ExampleBundle();
echo "   Bundle name: " . $exampleBundle->getName() . "\n";

// 3. Register bundle
echo "3. Registering bundle...\n";
$framework->registerBundle($exampleBundle);

// 4. Get bundle manager
echo "4. Getting bundle manager...\n";
$bundleManager = $framework->getBundleManager();
$bundles = $bundleManager->getBundles();
echo "   Registered bundles: " . count($bundles) . "\n";

foreach ($bundles as $name => $bundle) {
    echo "   - " . $name . " (" . get_class($bundle) . ")\n";
}

// 5. Register bundle services
echo "5. Registering bundle services...\n";
$framework->registerBundleServices();

// 6. Load bundle configuration
echo "6. Loading bundle configuration...\n";
$configLoader = new \Architect\Core\Bundle\Config\BundleConfigLoader();
$config = $configLoader->load($exampleBundle, $container);
echo "   Configuration loaded: " . (empty($config) ? 'No' : 'Yes') . "\n";

// 7. Discover bundle commands
echo "7. Discovering bundle commands...\n";
$commandRegistry = new \Architect\Core\Bundle\Command\BundleCommandRegistry();
$commands = $commandRegistry->discoverCommands($exampleBundle);
echo "   Commands discovered: " . count($commands) . "\n";

// 8. Get bundle migration directory
echo "8. Checking bundle migrations...\n";
$migrationManager = new \Architect\Core\Bundle\Migration\BundleMigrationManager();
$migrationDir = $migrationManager->getMigrationDirectory($exampleBundle);
echo "   Migration directory: " . ($migrationDir ?: 'Not found') . "\n";

// 9. Get bundle view directories
echo "9. Checking bundle views...\n";
$viewLoader = new \Architect\Core\Bundle\View\BundleViewLoader();
$viewDirs = $viewLoader->getViewDirectories($exampleBundle);
echo "   View directories: " . count($viewDirs) . "\n";

// 10. Get bundle asset directory
echo "10. Checking bundle assets...\n";
$assetPublisher = new \Architect\Core\Bundle\Asset\AssetPublisher();
$reflection = new ReflectionClass($exampleBundle);
$bundleDir = dirname($reflection->getFileName());
$assetsPath = $bundleDir . '/Resources/public';
echo "   Assets directory: " . (is_dir($assetsPath) ? 'Exists' : 'Not found') . "\n";

// 11. Load bundle routes
echo "11. Loading bundle routes...\n";
$routeLoader = new \Architect\Core\Bundle\Routing\BundleRouteLoader();
$routes = $routeLoader->load($exampleBundle);
echo "   Routes loaded: " . count($routes) . "\n";

// 12. Discover service providers
echo "12. Discovering service providers...\n";
$providerRegistry = new \Architect\Core\Bundle\ServiceProvider\BundleServiceProviderRegistry();
$providers = $providerRegistry->discoverServiceProviders($exampleBundle);
echo "   Service providers: " . count($providers) . "\n";

// 13. Test bundle service
echo "13. Testing bundle service...\n";
if ($container->has('example.service')) {
    $service = $container->get('example.service');
    echo "   Service initialized: " . ($service->isInitialized() ? 'Yes' : 'No') . "\n";
    
    $data = $service->getData();
    echo "   Service data:\n";
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            echo "     $key: " . implode(', ', $value) . "\n";
        } else {
            echo "     $key: $value\n";
        }
    }
} else {
    echo "   Service not found in container\n";
}

// 14. Test bundle auto-discovery
echo "14. Testing bundle auto-discovery...\n";
$discovery = new \Architect\Core\Bundle\BundleDiscovery();
$discoveredBundles = $discovery->discover();
echo "   Bundles discovered via Composer: " . count($discoveredBundles) . "\n";

// 15. Demonstrate bundle booting
echo "15. Booting bundles...\n";
$framework->bootBundles();
echo "   Bundles booted: " . ($bundleManager->isBooted() ? 'Yes' : 'No') . "\n";

echo "\n=== Demo Complete ===\n";
echo "The bundle system has been successfully demonstrated.\n";
echo "Key components implemented:\n";
echo "1. BundleInterface and AbstractBundle\n";
echo "2. BundleManager for bundle lifecycle management\n";
echo "3. BundleDiscovery for auto-discovery via Composer\n";
echo "4. BundleConfigLoader for configuration loading\n";
echo "5. AssetPublisher for asset management\n";
echo "6. BundleCommandRegistry for command registration\n";
echo "7. BundleMigrationManager for migration handling\n";
echo "8. BundleRouteLoader for route registration\n";
echo "9. BundleViewLoader for view/template integration\n";
echo "10. BundleServiceProviderRegistry for service provider integration\n";

echo "\nThe system is ready for use in Architect Framework projects.\n";