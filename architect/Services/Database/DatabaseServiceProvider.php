<?php

declare(strict_types=1);

namespace Architect\Services\Database;

use Architect\Contracts\ServiceProviderInterface;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Support\AbstractServiceProvider;

/**
 * Database service provider.
 * Registers DatabaseManager and connections in the container.
 */
class DatabaseServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // Register DatabaseManager as 'database'
        $this->registerFactory($container, 'database', function ($c) {
            $manager = new DatabaseManager($c);

            // Try to load configuration from config.database if already registered
            if ($c->has('config.database')) {
                $config = $c->get('config.database')->all();
                $manager->configure($config);
            } elseif ($c->has('config.loader')) {
                // Load database configuration via config loader with application-specific override
                try {
                    $loader = $c->get('config.loader');
                    $appPath = null;
                    if ($c->has('apps')) {
                        $apps = $c->get('apps');
                        $appPath = $apps->getAppDir();
                    }
                    $config = $loader->loadWithAppOverride('database', $appPath)->all();
                    $manager->configure($config);
                } catch (\Exception $e) {
                    // If configuration file is missing, manager remains unconfigured
                    // This will cause an error when trying to use a connection, which is intentional.
                }
            }
            // If neither config.database nor config.loader is available, manager stays unconfigured.

            return $manager;
        });

        // Alias 'db' to 'database' for convenience
        $this->registerFactory($container, 'db', fn($c) => $c->get('database'));
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container): void
    {
        // Optionally set up query logging integration with Debug service
        if ($container->has('debug')) {
            $this->setupQueryLogging($container);
        }
    }

    /**
     * Setup query logging to Debug service.
     */
    private function setupQueryLogging(ContainerInterface $container): void
    {
        $debug = $container->get('debug');
        $database = $container->get('database');

        if (!method_exists($debug, 'query')) {
            return;
        }

        // Prefer setQueryLogger if available
        if (method_exists($database, 'setQueryLogger')) {
            // Create an adapter that implements QueryLoggerInterface
            // Note: Don't check isEnabled() here - Debug::query() already checks internally
            $logger = new class($debug) implements \Architect\Services\Database\Contracts\QueryLoggerInterface {
                private $debug;
                public function __construct($debug) {
                    $this->debug = $debug;
                }
                public function logQuery(string $sql, float $duration, array $bindings = []): void {
                    $this->debug->query($sql, $duration, $bindings, 'database');
                }
            };
            $database->setQueryLogger($logger);
        } elseif (method_exists($database, 'setQueryCallback')) {
            $database->setQueryCallback(function (string $sql, float $duration, array $params = []) use ($debug) {
                $debug->query($sql, $duration, $params, 'database');
            });
        }

        // Setup query logging for Axiom ORM if available
        if (class_exists('Axiom\Orm\Connection\ConnectionManager')) {
            \Axiom\Orm\Connection\ConnectionManager::setQueryCallback(
                function (string $sql, float $duration, array $params = []) use ($debug) {
                    $debug->query($sql, $duration, $params, 'axiom');
                }
            );
        }
    }
}