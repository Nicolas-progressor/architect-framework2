<?php

declare(strict_types=1);

use Architect\Core\Container;
use Architect\Core\EnvironmentManager;
use Architect\Support\ServiceProviders\AggregateServiceProvider;
use Architect\Support\ServiceProviders\CoreServiceProvider;
use Architect\Support\ServiceProviders\RoutingServiceProvider;
use Architect\Support\ServiceProviders\MvcServiceProvider;
use Architect\Support\ServiceProviders\HttpServiceProvider;
use Architect\Support\ServiceProviders\LoggingServiceProvider;
use Architect\Support\ServiceProviders\ErrorServiceProvider;
use Architect\Support\ServiceProviders\AppsServiceProvider;
use Architect\Services\Template\TemplateServiceProvider;
use Architect\Services\I18n\LanguageServiceProvider;
use Architect\Services\Blueprint\BlueprintServiceProvider;
use Architect\Helpers\Core\HelpersServiceProvider;
use Architect\Support\ServiceProviders\CacheServiceProvider;
use Architect\Support\ServiceProviders\ProviderDiscovery;
use Architect\Console\ConsoleServiceProvider;

// Load Composer autoloader
if (!file_exists(ROOT_DIR . 'vendor/autoload.php')) {
    throw new RuntimeException('Composer autoloader not found. Run "composer install" first.');
}
require_once ROOT_DIR . 'vendor/autoload.php';

// Set environment (same as web)
putenv('APP_ENV=development');

// Initialize EnvironmentManager
$environment = new EnvironmentManager();

// Define APP_ENV constant
if (!defined('APP_ENV')) {
    define('APP_ENV', $environment->getEnvironment());
}

// Define APP_DEBUG constant based on environment
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', $environment->isDevelopment() || $environment->isTesting());
}

// Create container (DI)
$container = new Container();

// Register environment in container
$container->set('environment', $environment);

// Axiom ORM integration - after container is created
if (class_exists('Axiom\Orm\Integrations\Architect\AxiomBootstrap')) {
    \Axiom\Orm\Integrations\Architect\AxiomBootstrap::bootstrap($environment, $container);
}

// Create statement manager and register in container
$statement = new \Architect\Core\Statement($container);
$container->set('statement', $statement);

// Create framework for bundle management
$framework = new \Architect\Core\Framework($container, $statement);

// Create aggregate service provider with core providers
$aggregate = new AggregateServiceProvider([
    new CoreServiceProvider(),
    new AppsServiceProvider(),
    new RoutingServiceProvider(),
    new MvcServiceProvider(),
    new HttpServiceProvider(),
    new LoggingServiceProvider(),
    new ErrorServiceProvider(),
    new TemplateServiceProvider(),
    new LanguageServiceProvider(),
    new \Architect\Services\Database\DatabaseServiceProvider(),
    new HelpersServiceProvider(),
    new CacheServiceProvider(),
    new ConsoleServiceProvider($container),
]);

// Conditionally add Blueprint provider if available
if (class_exists('Blueprint\Engine\Blueprint') &&
    class_exists('Architect\Services\Blueprint\BlueprintServiceProvider')) {
    $aggregate->add(new BlueprintServiceProvider($container));
}

// Load discovered providers from cache, but skip Auth due to namespace mismatch
$discoveredProviders = ProviderDiscovery::loadFromCache();
foreach ($discoveredProviders as $providerClass) {
    // Skip problematic Auth provider (legacy namespace)
    if ($providerClass === 'Architect\\Auth\\AuthServiceProvider') {
        continue;
    }
    if (!class_exists($providerClass)) {
        // Try to autoload
        if (!@class_exists($providerClass)) {
            // Silently skip if class cannot be loaded
            continue;
        }
    }
    // Create provider instance with container if constructor expects it
    $provider = new $providerClass($container);
    $aggregate->add($provider);
}

// Register and boot bundles
$framework->registerBundlesFromDiscovery();
$framework->registerBundleServices();
$framework->bootBundles();

// Register all services (this will register console.registry etc.)
$aggregate->register($container);

// Boot services (this will trigger QueueServiceProvider::boot which registers commands)
$aggregate->boot($container);

// Configure statement hooks (needed for some providers)
$aggregate->configureStatements($statement);

// Return container for use in console
return $container;