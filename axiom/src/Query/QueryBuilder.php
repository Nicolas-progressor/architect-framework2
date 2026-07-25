<?php

declare(strict_types=1);

namespace Axiom\Orm\Query;

use Axiom\Orm\Connection\Connection;
use Axiom\Orm\Connection\ConnectionInterface;
use Axiom\Orm\Connection\ConnectionManager;
use Axiom\Orm\Exception\QueryException;
use PDO;

class QueryBuilder
{
    private ?ConnectionInterface $connection = null;

    private string $type = 'select';

    private array $selects = [];

    private ?string $from = null;

    private array $joins = [];

    private array $wheres = [];

    private array $groups = [];

    private array $havings = [];

    private array $orders = [];

    private ?int $limit = null;

    private ?int $offset = null;

    private bool $distinct = false;

    private ?string $table = null;

    private ?array $set = null;

    private ?string $entityClass = null;

    /**
     * Create new QueryBuilder instance
     * Connection is resolved lazily when first query is executed
     */
    public function __construct(?ConnectionInterface $connection = null)
    {
        $this->connection = $connection;
    }

    /**
     * Resolve connection - lazy initialization
     * Gets connection on first access to the database
     * 
     * @throws QueryException if no connection configured
     */
    private function resolveConnection(): ConnectionInterface
    {
        if ($this->connection === null) {
            $this->connection = $this->getDefaultConnection();
        }
        return $this->connection;
    }

    /**
     * Get default connection from ConnectionManager
     * 
     * @throws QueryException if no connection configured
     */
    private function getDefaultConnection(): ConnectionInterface
    {
        if (!ConnectionManager::isConfigured()) {
            throw QueryException::missingTable();
        }
        return ConnectionManager::getDefault();
    }

    /**
     * Set connection explicitly
     */
    public function setConnection(ConnectionInterface $connection): self
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Get current connection (resolves if not set)
     * 
     * @throws QueryException if no connection configured
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->resolveConnection();
    }

    // ========== SELECT MODIFIERS ==========

    /**
     * Set columns to select
     */
    public function select(array|string $columns = ['*']): self
    {
        $this->type = 'select';
        $this->selects = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Add select column
     */
    public function addSelect(string|array $column): self
    {
        $this->selects = array_merge($this->selects, is_array($column) ? $column : [$column]);
        return $this;
    }

    /**
     * Add distinct modifier
     */
    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Set table
     */
    public function from(string $table): self
    {
        $this->from = $table;
        return $this;
    }

    /**
     * Alias for from()
     */
    public function table(string $table): self
    {
        return $this->from($table);
    }

    /**
     * Add inner join
     */
    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'INNER',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        return $this;
    }

    /**
     * Add left join
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'LEFT',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        return $this;
    }

    /**
     * Add right join
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'RIGHT',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        return $this;
    }

    /**
     * Add where condition
     */
    public function where(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        return $this;
    }

    /**
     * Add and where condition
     */
    public function andWhere(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        return $this;
    }

    /**
     * Add or where condition
     */
    public function orWhere(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'OR'
        ];
        return $this;
    }

    /**
     * Add where in condition
     */
    public function whereIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND',
            'not' => false
        ];
        return $this;
    }

    /**
     * Add or where in condition
     */
    public function orWhereIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'OR',
            'not' => false
        ];
        return $this;
    }

    /**
     * Add where not in condition
     */
    public function whereNotIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND',
            'not' => true
        ];
        return $this;
    }

    /**
     * Add where null condition
     */
    public function whereNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'AND',
            'not' => false
        ];
        return $this;
    }

    /**
     * Add where not null condition
     */
    public function whereNotNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'AND',
            'not' => true
        ];
        return $this;
    }

    /**
     * Add or where null condition
     */
    public function orWhereNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'OR',
            'not' => false
        ];
        return $this;
    }

    /**
     * Add where between condition
     */
    public function whereBetween(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND',
            'not' => false
        ];
        return $this;
    }

    /**
     * Add where not between condition
     */
    public function whereNotBetween(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND',
            'not' => true
        ];
        return $this;
    }

    /**
     * Add raw where condition
     */
    public function whereRaw(string $sql, array $bindings = []): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'bindings' => $bindings,
            'boolean' => 'AND'
        ];
        return $this;
    }

    /**
     * Add or raw where condition
     */
    public function orWhereRaw(string $sql, array $bindings = []): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'bindings' => $bindings,
            'boolean' => 'OR'
        ];
        return $this;
    }

    /**
     * Add group by
     */
    public function groupBy(array|string $columns): self
    {
        $this->groups = array_merge($this->groups, is_array($columns) ? $columns : [$columns]);
        return $this;
    }

    /**
     * Add having condition
     */
    public function having(string $column, string $operator, mixed $value): self
    {
        $this->havings[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        return $this;
    }

    /**
     * Add and having condition
     */
    public function andHaving(string $column, string $operator, mixed $value): self
    {
        $this->havings[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        return $this;
    }

    /**
     * Add or having condition
     */
    public function orHaving(string $column, string $operator, mixed $value): self
    {
        $this->havings[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'OR'
        ];
        return $this;
    }

    /**
     * Add raw having condition
     */
    public function havingRaw(string $sql, array $bindings = []): self
    {
        $this->havings[] = [
            'type' => 'raw',
            'sql' => $sql,
            'bindings' => $bindings,
            'boolean' => 'AND'
        ];
        return $this;
    }

    /**
     * Add order by
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];
        return $this;
    }

    /**
     * Set limit
     */
    public function limit(int $value): self
    {
        $this->limit = $value;
        return $this;
    }

    /**
     * Set offset
     */
    public function offset(int $value): self
    {
        $this->offset = $value;
        return $this;
    }

    // ========== INSERT MODIFIERS ==========

    /**
     * Start insert query
     * If table name is provided, sets the table
     * Otherwise uses the table already set via table() method (from)
     */
    public function insert(?string $table = null): self
    {
        $this->type = 'insert';
        if ($table !== null) {
            $this->table = $table;
        } elseif ($this->from !== null) {
            // Use table from from() method if no table specified
            $this->table = $this->from;
        }
        $this->selects = [];
        return $this;
    }

    /**
     * Set data for insert/update
     */
    public function set(array $data): self
    {
        $this->set = $data;
        return $this;
    }

    // ========== UPDATE MODIFIERS ==========

    /**
     * Start update query
     */
    public function update(string $table): self
    {
        $this->type = 'update';
        $this->table = $table;
        return $this;
    }

    // ========== DELETE MODIFIERS ==========

    /**
     * Start delete query
     */
    public function delete(?string $table = null): self
    {
        $this->type = 'delete';
        if ($table !== null) {
            $this->table = $table;
        }
        return $this;
    }

    // ========== AGGREGATE FUNCTIONS ==========

    /**
     * Count records
     */
    public function count(string $column = '*'): int
    {
        $this->selects = ["COUNT({$column}) as aggregate"];
        $result = $this->first();
        return (int) ($result['aggregate'] ?? 0);
    }

    /**
     * Sum column
     */
    public function sum(string $column): int|float
    {
        $this->selects = ["SUM({$column}) as aggregate"];
        $result = $this->first();
        return (int|float) ($result['aggregate'] ?? 0);
    }

    /**
     * Average column
     */
    public function avg(string $column): int|float
    {
        $this->selects = ["AVG({$column}) as aggregate"];
        $result = $this->first();
        return (int|float) ($result['aggregate'] ?? 0);
    }

    /**
     * Max column value
     */
    public function max(string $column): mixed
    {
        $this->selects = ["MAX({$column}) as aggregate"];
        $result = $this->first();
        return $result['aggregate'] ?? null;
    }

    /**
     * Min column value
     */
    public function min(string $column): mixed
    {
        $this->selects = ["MIN({$column}) as aggregate"];
        $result = $this->first();
        return $result['aggregate'] ?? null;
    }

    // ========== RAW QUERIES ==========

    /**
     * Execute raw query
     */
    public function raw(string $sql, array $bindings = []): self
    {
        $this->type = 'raw';
        $this->selects = [];
        $this->wheres = [];
        $this->rawSql = $sql;
        $this->rawBindings = $bindings;
        return $this;
    }

    private ?string $rawSql = null;
    private array $rawBindings = [];

    /**
     * Add raw select expression
     */
    public function selectRaw(string $expression, array $bindings = []): self
    {
        $this->selects[] = [
            'type' => 'raw',
            'expression' => $expression,
            'bindings' => $bindings
        ];
        return $this;
    }

    // ========== ENTITY SUPPORT ==========

    /**
     * Set entity class for result mapping
     */
    public function entity(string $class): self
    {
        $this->entityClass = $class;
        return $this;
    }

    /**
     * Get entity class
     */
    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    // ========== EXECUTION METHODS ==========

    /**
     * Execute query and return all results
     */
    public function get(): array
    {
        $connection = $this->resolveConnection();
        $sql = $this->buildSelect();
        $bindings = $this->collectBindings();

        $stmt = $connection->query($sql, $bindings);
        $results = $stmt->fetchAll();

        if ($this->entityClass && class_exists($this->entityClass)) {
            return $this->mapToEntities($results);
        }

        return $results;
    }

    /**
     * Execute query and return first result
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Execute query and return first column value
     */
    public function pluck(string $column): array
    {
        $this->selects = [$column];
        $results = $this->get();
        return array_column($results, $column);
    }

    /**
     * Execute insert/update/delete and return affected rows or last insert id
     */
    public function execute(): int
    {
        $connection = $this->resolveConnection();
        $sql = $this->buildStatement();
        $bindings = $this->collectBindings();

        $stmt = $connection->query($sql, $bindings);
        
        // For INSERT, return last insert ID
        if ($this->type === 'insert') {
            return (int) $connection->getPdo()->lastInsertId();
        }
        
        return $stmt->rowCount();
    }

    /**
     * Check if records exist
     */
    public function exists(): bool
    {
        $connection = $this->resolveConnection();
        $originalSelects = $this->selects;
        $this->selects = [1];

        $sql = $this->buildSelect();
        $sql = "SELECT 1 FROM ({$sql}) AS exists_check LIMIT 1";

        $bindings = $this->collectBindings();
        $stmt = $connection->query($sql, $bindings);
        $result = $stmt->fetch();

        $this->selects = $originalSelects;

        return !empty($result);
    }

    // ========== TRANSACTION METHODS ==========

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->resolveConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->resolveConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollBack(): bool
    {
        return $this->resolveConnection()->rollBack();
    }

    // ========== SQL BUILDING ==========

    /**
     * Build SELECT query
     */
    private function buildSelect(): string
    {
        $parts = [];

        // SELECT
        $columns = !empty($this->selects) ? $this->selects : ['*'];
        $selectStr = $this->distinct ? 'SELECT DISTINCT ' : 'SELECT ';
        $selectStr .= implode(', ', $this->buildColumns($columns));
        $parts[] = $selectStr;

        // FROM
        if ($this->from) {
            $parts[] = "FROM {$this->from}";
        }

        // JOINs
        foreach ($this->joins as $join) {
            $parts[] = "{$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        // WHERE
        if (!empty($this->wheres)) {
            $parts[] = 'WHERE ' . $this->buildWheres();
        }

        // GROUP BY
        if (!empty($this->groups)) {
            $parts[] = 'GROUP BY ' . implode(', ', $this->groups);
        }

        // HAVING
        if (!empty($this->havings)) {
            $parts[] = 'HAVING ' . $this->buildHavings();
        }

        // ORDER BY
        if (!empty($this->orders)) {
            $orderParts = [];
            foreach ($this->orders as $order) {
                $orderParts[] = "{$order['column']} {$order['direction']}";
            }
            $parts[] = 'ORDER BY ' . implode(', ', $orderParts);
        }

        // LIMIT
        if ($this->limit !== null) {
            $parts[] = "LIMIT {$this->limit}";
        }

        // OFFSET
        if ($this->offset !== null) {
            $parts[] = "OFFSET {$this->offset}";
        }

        return implode(' ', array_filter($parts));
    }

    /**
     * Build INSERT/UPDATE/DELETE statement
     */
    private function buildStatement(): string
    {
        return match ($this->type) {
            'insert' => $this->buildInsert(),
            'update' => $this->buildUpdate(),
            'delete' => $this->buildDelete(),
            'raw' => $this->rawSql ?? '',
            default => $this->buildSelect(),
        };
    }

    private function buildInsert(): string
    {
        $table = $this->table;
        $columns = array_keys($this->set ?? []);
        $values = array_values($this->set ?? []);

        $columnStr = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        return "INSERT INTO {$table} ({$columnStr}) VALUES ({$placeholders})";
    }

    private function buildUpdate(): string
    {
        $table = $this->table;
        $sets = [];

        foreach ($this->set ?? [] as $column => $value) {
            $sets[] = "{$column} = ?";
        }

        return "UPDATE {$table} SET " . implode(', ', $sets) . $this->buildWhereClause();
    }

    private function buildDelete(): string
    {
        $table = $this->table ?? $this->from;
        return "DELETE FROM {$table}" . $this->buildWhereClause();
    }

    private function buildWhereClause(): string
    {
        if (empty($this->wheres)) {
            return '';
        }
        return ' WHERE ' . $this->buildWheres();
    }

    /**
     * Build columns list
     */
    private function buildColumns(array $columns): array
    {
        $result = [];
        foreach ($columns as $column) {
            if (is_array($column) && isset($column['type']) && $column['type'] === 'raw') {
                $result[] = $column['expression'];
                if (!empty($column['bindings'])) {
                    $this->addBindings($column['bindings']);
                }
            } else {
                $result[] = (string) $column;
            }
        }
        return $result;
    }

    /**
     * Build WHERE clause
     */
    private function buildWheres(): string
    {
        $conditions = [];

        foreach ($this->wheres as $index => $where) {
            $boolean = $index === 0 ? '' : "{$where['boolean']} ";

            $condition = match ($where['type']) {
                'basic' => "{$where['column']} {$where['operator']} ?",
                'in' => $this->buildInCondition($where),
                'null' => $this->buildNullCondition($where),
                'between' => $this->buildBetweenCondition($where),
                'raw' => $where['sql'],
                default => ''
            };

            $conditions[] = $boolean . $condition;
        }

        return implode(' ', $conditions);
    }

    private function buildInCondition(array $where): string
    {
        $not = $where['not'] ? 'NOT ' : '';
        $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
        return "{$not}{$where['column']} IN ({$placeholders})";
    }

    private function buildNullCondition(array $where): string
    {
        $not = $where['not'] ? 'NOT ' : '';
        return "{$not}{$where['column']} IS NULL";
    }

    private function buildBetweenCondition(array $where): string
    {
        $not = $where['not'] ? 'NOT ' : '';
        return "{$not}{$where['column']} BETWEEN ? AND ?";
    }

    /**
     * Build HAVING clause
     */
    private function buildHavings(): string
    {
        $conditions = [];

        foreach ($this->havings as $index => $having) {
            $boolean = $index === 0 ? '' : "{$having['boolean']} ";

            $condition = match ($having['type']) {
                'basic' => "{$having['column']} {$having['operator']} ?",
                'raw' => $having['sql'],
                default => ''
            };

            $conditions[] = $boolean . $condition;
        }

        return implode(' ', $conditions);
    }

    /**
     * Collect all bindings
     */
    private function collectBindings(): array
    {
        $bindings = [];

        // For UPDATE/INSERT: SET values first, then WHERE
        // For SELECT/DELETE: WHERE first, then other
        if ($this->type === 'update' || $this->type === 'insert') {
            // Collect from SET first (for UPDATE/INSERT)
            if ($this->set) {
                $bindings = array_merge($bindings, array_values($this->set));
            }
        }

        // Collect from WHERE
        foreach ($this->wheres as $where) {
            if (isset($where['value'])) {
                $bindings[] = $where['value'];
            } elseif (isset($where['values'])) {
                $bindings = array_merge($bindings, $where['values']);
            } elseif (isset($where['bindings'])) {
                $bindings = array_merge($bindings, $where['bindings']);
            }
        }

        // Collect from HAVING
        foreach ($this->havings as $having) {
            if (isset($having['value'])) {
                $bindings[] = $having['value'];
            } elseif (isset($having['bindings'])) {
                $bindings = array_merge($bindings, $having['bindings']);
            }
        }

        // For SELECT/DELETE: SET values after WHERE (if any)
        if ($this->type !== 'update' && $this->type !== 'insert') {
            if ($this->set) {
                $bindings = array_merge($bindings, array_values($this->set));
            }
        }

        // Collect from raw query
        if (!empty($this->rawBindings)) {
            $bindings = array_merge($bindings, $this->rawBindings);
        }

        return $bindings;
    }

    private function addBindings(array $bindings): void
    {
        // This is handled in collectBindings for raw selects
    }

    // ========== ENTITY MAPPING ==========

    /**
     * Map results to entity objects
     */
    private function mapToEntities(array $results): array
    {
        $entities = [];
        $class = $this->entityClass;

        foreach ($results as $result) {
            $entities[] = $this->mapToEntity($result, $class);
        }

        return $entities;
    }

    /**
     * Map single row to entity
     */
    private function mapToEntity(array $data, string $class): object
    {
        $entity = new $class();

        foreach ($data as $property => $value) {
            $setter = 'set' . ucfirst($this->camelCase($property));
            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
            } elseif (property_exists($entity, $property)) {
                $entity->$property = $value;
            }
        }

        return $entity;
    }

    /**
     * Convert snake_case to camelCase
     */
    private function camelCase(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }

}
