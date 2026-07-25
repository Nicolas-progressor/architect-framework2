<?php

declare(strict_types=1);

namespace Axiom\Orm\Integrations\Architect;

use Axiom\Orm\Orm as AxiomOrm;
use Axiom\Orm\Query\QueryBuilder;

/**
 * Trait for integrating Axiom ORM with Architect ModelBase
 * 
 * Usage in your model:
 * 
 * ```php
 * use Architect\Services\Mvc\ModelBase;
 * use Axiom\Orm\Integrations\Architect\ModelOrmTrait;
 * 
 * class UserModel extends ModelBase
 * {
 *     use ModelOrmTrait;
 *     
 *     public function getActiveUsers()
 *     {
 *         return $this->db()
 *             ->from('users')
 *             ->where('status', '=', 'active')
 *             ->get();
 *     }
 * }
 * ```
 */
trait ModelOrmTrait
{
    /**
     * Get QueryBuilder instance
     * Shortcut for $this->get('orm')->query()
     */
    protected function db(): QueryBuilder
    {
        // Try to get from container if available
        if (method_exists($this, 'get') && isset($this->container)) {
            try {
                $orm = $this->get('orm');
                if ($orm instanceof QueryBuilder) {
                    return $orm;
                }
                if (is_object($orm) && method_exists($orm, 'query')) {
                    return $orm->query();
                }
            } catch (\Exception $fallback) {
                // Fallback to direct ORM
            }
        }

        // Direct ORM usage
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
     * Execute raw SQL query
     */
    protected function raw(string $sql, array $bindings = []): QueryBuilder
    {
        return AxiomOrm::raw($sql, $bindings);
    }

    /**
     * Execute callback in transaction
     */
    protected function transaction(callable $callback): mixed
    {
        return AxiomOrm::transaction($callback);
    }

    /**
     * Get ORM config
     */
    protected function getOrmConfig(string $name = 'default'): array
    {
        return \Axiom\Orm\Connection\ConnectionManager::getConfig($name);
    }
}
