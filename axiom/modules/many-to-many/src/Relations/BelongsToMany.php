<?php

declare(strict_types=1);

namespace Axiom\ManyToMany;

use Axiom\Orm\Query\QueryBuilder;
use Axiom\Orm\Connection\ConnectionManager;

/**
 * Many-to-Many relationship handler
 */
class BelongsToMany
{
    private string $related;
    private string $pivotTable;
    private string $foreignPivotKey;
    private string $relatedPivotKey;
    private string $parentKey;
    private string $relatedKey;
    private array $pivotColumns = [];
    private ?QueryBuilder $parentQuery = null;
    private $parentId;

    /**
     * Create many-to-many relationship
     */
    public function __construct(
        string $related,
        ?string $pivotTable = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null
    ) {
        $this->related = $related;
        
        // Derive pivot table name from model names
        $this->pivotTable = $pivotTable ?? $this->derivePivotTable();
        
        // Default foreign keys
        $this->foreignPivotKey = $foreignPivotKey ?? $this->getDefaultForeignKey();
        $this->relatedPivotKey = $relatedPivotKey ?? $this->getDefaultRelatedKey();
        
        // Default primary keys
        $this->parentKey = 'id';
        $this->relatedKey = 'id';
    }

    /**
     * Derive pivot table name from related models
     */
    private function derivePivotTable(): string
    {
        $models = [
            strtolower(basename(str_replace('\\', '/', $this->related))),
            strtolower(basename(str_replace('\\', '/', $this->parentQuery ? get_class($this->parentQuery) : 'parent')))
        ];
        
        sort($models);
        
        return implode('_', $models);
    }

    /**
     * Get default foreign key
     */
    private function getDefaultForeignKey(): string
    {
        return strtolower(basename(str_replace('\\', '/', $this->parentQuery ? get_class($this->parentQuery) : 'parent'))) . '_id';
    }

    /**
     * Get default related key
     */
    private function getDefaultRelatedKey(): string
    {
        return strtolower(basename(str_replace('\\', '/', $this->related))) . '_id';
    }

    /**
     * Set parent query builder
     */
    public function setParent(QueryBuilder $query, $parentId): self
    {
        $this->parentQuery = $query;
        $this->parentId = $parentId;
        return $this;
    }

    /**
     * Set pivot table
     */
    public function using(string $pivotTable): self
    {
        $this->pivotTable = $pivotTable;
        return $this;
    }

    /**
     * Set pivot columns to select
     */
    public function withPivot(array $columns): self
    {
        $this->pivotColumns = $columns;
        return $this;
    }

    /**
     * Get related models
     */
    public function get(): array
    {
        $query = $this->getQuery();
        
        return $query->get();
    }

    /**
     * Get first related model
     */
    public function first(): ?array
    {
        $query = $this->getQuery();
        
        return $query->first();
    }

    /**
     * Count related models
     */
    public function count(): int
    {
        $query = $this->getBaseQuery();
        
        return $query->count($this->pivotTable . '.' . $this->foreignPivotKey);
    }

    /**
     * Check if has related model
     */
    public function has($relatedId): bool
    {
        return (bool) $this->wherePivot($this->relatedPivotKey, '=', $relatedId)->count();
    }

    /**
     * Attach related model(s)
     */
    public function attach($id, array $pivotData = []): void
    {
        $ids = is_array($id) ? $id : [$id];
        
        foreach ($ids as $id) {
            $this->insertPivot($id, $pivotData);
        }
    }

    /**
     * Detach related model(s)
     */
    public function detach($id = null): int
    {
        $query = QueryBuilder::table($this->pivotTable);
        
        if ($id !== null) {
            $ids = is_array($id) ? $id : [$id];
            $query->whereIn($this->foreignPivotKey, $ids);
        }
        
        $query->where($this->foreignPivotKey, '=', $this->parentId);
        
        return $query->delete();
    }

    /**
     * Sync related models
     */
    public function sync(array $ids, bool $detach = true): void
    {
        $current = $this->allPivotIds();
        $ids = array_map('intval', $ids);
        
        // Detach models not in the new list
        if ($detach) {
            $toDetach = array_diff($current, $ids);
            if (!empty($toDetach)) {
                $this->detach($toDetach);
            }
        }
        
        // Attach new models
        $toAttach = array_diff($ids, $current);
        if (!empty($toAttach)) {
            $this->attach($toAttach);
        }
    }

    /**
     * Toggle attachment
     */
    public function toggle(array $ids): void
    {
        $current = $this->allPivotIds();
        $ids = array_map('intval', $ids);
        
        foreach ($ids as $id) {
            if (in_array($id, $current)) {
                $this->detach($id);
            } else {
                $this->attach($id);
            }
        }
    }

    /**
     * Update pivot data
     */
    public function updatePivot($relatedId, array $data): int
    {
        return QueryBuilder::table($this->pivotTable)
            ->where($this->foreignPivotKey, '=', $this->parentId)
            ->where($this->relatedPivotKey, '=', $relatedId)
            ->set($data)
            ->update()
            ->execute();
    }

    /**
     * Add where condition for pivot
     */
    public function wherePivot(string $column, string $operator, mixed $value): self
    {
        $this->pivotColumns[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        
        return $this;
    }

    /**
     * Get all pivot IDs
     */
    public function allPivotIds(): array
    {
        return QueryBuilder::table($this->pivotTable)
            ->pluck($this->relatedPivotKey);
    }

    /**
     * Get query with join
     */
    private function getQuery(): QueryBuilder
    {
        $query = $this->getBaseQuery();
        
        $relatedTable = $this->getRelatedTable();
        
        $query->join(
            $this->pivotTable,
            $relatedTable . '.' . $this->relatedKey,
            '=',
            $this->pivotTable . '.' . $this->relatedPivotKey
        );
        
        $query->where($this->pivotTable . '.' . $this->foreignPivotKey, '=', $this->parentId);
        
        // Add pivot conditions
        foreach ($this->pivotColumns as $column) {
            if (is_array($column)) {
                $query->where($this->pivotTable . '.' . $column['column'], $column['operator'], $column['value']);
            }
        }
        
        return $query;
    }

    /**
     * Get base query
     */
    private function getBaseQuery(): QueryBuilder
    {
        $relatedTable = $this->getRelatedTable();
        
        $columns = [$relatedTable . '.*'];
        
        // Add pivot columns
        if (!empty($this->pivotColumns) || true) {
            $columns[] = $this->pivotTable . '.' . $this->foreignPivotKey . ' as pivot_' . $this->foreignPivotKey;
            $columns[] = $this->pivotTable . '.' . $this->relatedPivotKey . ' as pivot_' . $this->relatedPivotKey;
            
            foreach ($this->pivotColumns as $column) {
                if (is_string($column)) {
                    $columns[] = $this->pivotTable . '.' . $column . ' as pivot_' . $column;
                }
            }
        }
        
        return QueryBuilder::table($relatedTable)
            ->select($columns)
            ->distinct();
    }

    /**
     * Get related table name
     */
    private function getRelatedTable(): string
    {
        // Convert class name to table name
        $class = $this->related;
        
        // If it's a class with __invoke or static method
        if (class_exists($class)) {
            // Try to get table name from model
            if (method_exists($class, 'getTable')) {
                return $class::getTable();
            }
            
            // Derive from class name
            $name = basename(str_replace('\\', '/', $class));
            return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
        }
        
        return $this->related;
    }

    /**
     * Insert pivot record
     */
    private function insertPivot($relatedId, array $pivotData): void
    {
        $data = [
            $this->foreignPivotKey => $this->parentId,
            $this->relatedPivotKey => $relatedId
        ];
        
        foreach ($pivotData as $key => $value) {
            $data[$key] = $value;
        }
        
        QueryBuilder::table($this->pivotTable)
            ->insert($this->pivotTable)
            ->set($data)
            ->execute();
    }
}

/**
 * Helper for defining relationships in models
 */
trait BelongsToManyTrait
{
    /**
     * Define many-to-many relationship
     */
    public function belongsToMany(
        string $related,
        ?string $pivotTable = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null
    ): BelongsToMany {
        $relation = new BelongsToMany(
            $related,
            $pivotTable,
            $foreignPivotKey,
            $relatedPivotKey
        );
        
        // Get ID from current model
        $id = $this->getId() ?? null;
        
        $query = QueryBuilder::table($this->getTable() ?? $this->deriveTableName());
        
        return $relation->setParent($query, $id);
    }

    /**
     * Derive table name from model class
     */
    private function deriveTableName(): string
    {
        $name = basename(str_replace('\\', '/', static::class));
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }

    /**
     * Get ID (to be implemented in model)
     */
    protected function getId(): mixed
    {
        return $this->id ?? null;
    }

    /**
     * Get table name (to be implemented in model)
     */
    protected function getTable(): ?string
    {
        return static::$table ?? null;
    }
}

/**
 * Relationship query builder
 */
class RelationQueryBuilder
{
    private QueryBuilder $query;
    private BelongsToMany $relation;

    public function __construct(QueryBuilder $query, BelongsToMany $relation)
    {
        $this->query = $query;
        $this->relation = $relation;
    }

    /**
     * Add where condition
     */
    public function where(string $column, string $operator, mixed $value): self
    {
        $this->query->where($column, $operator, $value);
        return $this;
    }

    /**
     * Add order by
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    /**
     * Add limit
     */
    public function limit(int $limit): self
    {
        $this->query->limit($limit);
        return $this;
    }

    /**
     * Execute query
     */
    public function get(): array
    {
        return $this->relation->get();
    }

    /**
     * Get first result
     */
    public function first(): ?array
    {
        return $this->relation->first();
    }

    /**
     * Count results
     */
    public function count(): int
    {
        return $this->relation->count();
    }
}
