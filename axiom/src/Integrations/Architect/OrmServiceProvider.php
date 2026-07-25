<?php

declare(strict_types=1);

namespace Axiom\Orm\Integrations\Architect;

use Axiom\Orm\Connection\ConnectionManager;
use Axiom\Orm\Orm as AxiomOrm;
use Axiom\Orm\Query\QueryBuilder;

/**
 * Service provider for integrating Axiom ORM with Architect Framework
 */
class OrmServiceProvider
{
    /**
     * Register ORM services in Architect container
     */
    public static function register(array $config): void
    {
        // Load ORM configuration
        $ormConfig = $config['orm'] ?? $config['database'] ?? [];
        if (!empty($ormConfig)) {
            AxiomOrm::loadConfig($ormConfig);
        }

        // Register in container if available
        $container = self::getContainer();
        if ($container !== null) {
            self::registerInContainer($container);
        }
    }

    /**
     * Try to get Architect container
     */
    #[\ReturnTypeWillChange]
    private static function getContainer()
    {
        if (class_exists(\Architect\Core\Container::class)) {
            return \Architect\Core\Container::getInstance();
        }
        return null;
    }

    /**
     * Register services in container
     */
    private static function registerInContainer(object $container): void
    {
        // Register 'db' alias for QueryBuilder
        $container->set('db', function () {
            return new QueryBuilder();
        });

        // Register 'orm' alias for full ORM class
        $container->set('orm', function () {
            return new class {
                public function query(): QueryBuilder
                {
                    return AxiomOrm::query();
                }

                public function table(string $table): QueryBuilder
                {
                    return AxiomOrm::table($table);
                }

                public function raw(string $sql, array $bindings = []): QueryBuilder
                {
                    return AxiomOrm::raw($sql, $bindings);
                }

                public function connection(string $name = 'default')
                {
                    return AxiomOrm::connection($name);
                }

                public function beginTransaction(): bool
                {
                    return AxiomOrm::beginTransaction();
                }

                public function commit(): bool
                {
                    return AxiomOrm::commit();
                }

                public function rollBack(): bool
                {
                    return AxiomOrm::rollBack();
                }

                public function transaction(callable $callback): mixed
                {
                    return AxiomOrm::transaction($callback);
                }

                public function getConfig(string $name = 'default'): array
                {
                    return ConnectionManager::getConfig($name);
                }
            };
        });
    }

    /**
     * Bootstrap ORM with Architect
     */
    public static function bootstrap(array $config = []): void
    {
        // Load config from file if not provided
        if (empty($config)) {
            $configPath = APP_DIR . 'config/database.json';
            if (file_exists($configPath)) {
                $config = json_decode(file_get_contents($configPath), true);
            }
        }

        if (!empty($config)) {
            self::register($config);
        }
    }
}

/**
 * Helper trait for Models to use ORM
 */
trait OrmTrait
{
    /**
     * Get QueryBuilder instance
     */
    protected function orm(): QueryBuilder
    {
        return AxiomOrm::query();
    }

    /**
     * Get QueryBuilder for specific table
     */
    protected function table(string $table): QueryBuilder
    {
        return AxiomOrm::table($table);
    }

    /**
     * Execute raw SQL
     */
    protected function raw(string $sql, array $bindings = []): QueryBuilder
    {
        return AxiomOrm::raw($sql, $bindings);
    }
}

/**
 * Helper trait for Models to use transactions
 */
trait TransactionTrait
{
    /**
     * Execute callback in transaction
     */
    protected function transaction(callable $callback): mixed
    {
        return AxiomOrm::transaction($callback);
    }
}
