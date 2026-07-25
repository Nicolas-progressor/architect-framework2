<?php

declare(strict_types=1);

use Architect\Core\Container;
use Architect\Core\Framework;
use Architect\Core\Statement;
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

// Enable error logging for debugging
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');

// Initialize EnvironmentManager early (before container and services)
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
$statement = new Statement($container);
$container->set('statement', $statement);

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
    new \Architect\Console\ConsoleServiceProvider($container),
]);
// Conditionally add Blueprint provider if available
if (class_exists('Blueprint\Engine\Blueprint') &&
    class_exists('Architect\Services\Blueprint\BlueprintServiceProvider')) {
    $aggregate->add(new BlueprintServiceProvider($container));
}

// Load discovered providers from cache
$discoveredProviders = ProviderDiscovery::loadFromCache();
foreach ($discoveredProviders as $providerClass) {
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

// Register all services
$aggregate->register($container);

// Create framework (needs statement)
$framework = new Framework($container, $statement);

// Register and boot bundles
$framework->registerBundlesFromDiscovery();
$framework->registerBundleServices();
$framework->bootBundles();

// Boot services
$aggregate->boot($container);

// Configure statement hooks
$aggregate->configureStatements($statement);

// Run application
$framework->run();
