<?php

declare(strict_types=1);

namespace Axiom\Entity;

use Axiom\Orm\Query\QueryBuilder;
use Axiom\Orm\Orm;

/**
 * Repository for entity operations
 */
class Repository
{
    protected string $entityClass;
    protected EntityMetadata $metadata;
    protected ?string $connection = null;

    public function __construct(string $entityClass, ?string $connection = null)
    {
        $this->entityClass = $entityClass;
        $this->connection = $connection;
        $this->metadata = EntityManager::getMetadata($entityClass);
    }

    /**
     * Get QueryBuilder for this entity
     */
    protected function query(): QueryBuilder
    {
        $query = Orm::table($this->metadata->table);
        
        if ($this->connection) {
            $query->setConnection(Orm::connection($this->connection));
        }
        
        return $query;
    }

    /**
     * Find all entities
     */
    public function findAll(): array
    {
        $results = $this->query()->get();
        
        return array_map(
            fn($row) => EntityManager::map($this->entityClass, $row),
            $results
        );
    }

    /**
     * Find entity by ID
     */
    public function find(int|string $id): ?object
    {
        $pk = $this->metadata->getPrimaryKey();
        
        if ($pk === null) {
            throw new \RuntimeException("Entity {$this->entityClass} has no primary key");
        }
        
        $row = $this->query()
            ->where($pk, '=', $id)
            ->first();
        
        if ($row === null) {
            return null;
        }
        
        return EntityManager::map($this->entityClass, $row);
    }

    /**
     * Find entities by criteria
     */
    public function findBy(array $criteria): array
    {
        $query = $this->query();
        
        foreach ($criteria as $column => $value) {
            $query->where($column, '=', $value);
        }
        
        $results = $query->get();
        
        return array_map(
            fn($row) => EntityManager::map($this->entityClass, $row),
            $results
        );
    }

    /**
     * Find first entity by criteria
     */
    public function findOneBy(array $criteria): ?object
    {
        $results = $this->findBy($criteria);
        return $results[0] ?? null;
    }

    /**
     * Save entity (insert or update)
     */
    public function save(object $entity): int|string
    {
        $pk = $this->metadata->getPrimaryKey();
        
        // Get current ID
        $getter = 'get' . ucfirst($this->metadata->idProperty);
        $id = method_exists($entity, $getter) ? $entity->$getter() : ($entity->{$this->metadata->idProperty} ?? null);
        
        $mapped = EntityManager::toArray($entity);
        $data = $mapped['data'];
        
        if ($id !== null && $id !== 0) {
            // Update
            return $this->query()
                ->update($this->metadata->table)
                ->set($data)
                ->where($pk, '=', $id)
                ->execute();
        }
        
        // Insert
        return $this->query()
            ->insert($this->metadata->table)
            ->set($data)
            ->execute();
    }

    /**
     * Delete entity
     */
    public function delete(object $entity): int
    {
        $pk = $this->metadata->getPrimaryKey();
        
        if ($pk === null) {
            throw new \RuntimeException("Entity {$this->entityClass} has no primary key");
        }
        
        $getter = 'get' . ucfirst($this->metadata->idProperty);
        $id = method_exists($entity, $getter) ? $entity->$getter() : ($entity->{$this->metadata->idProperty} ?? null);
        
        if ($id === null) {
            throw new \RuntimeException("Cannot delete entity without primary key");
        }
        
        return $this->query()
            ->delete()
            ->where($pk, '=', $id)
            ->execute();
    }

    /**
     * Delete by ID
     */
    public function deleteById(int|string $id): int
    {
        $pk = $this->metadata->getPrimaryKey();
        
        return $this->query()
            ->delete()
            ->where($pk, '=', $id)
            ->execute();
    }

    /**
     * Count entities
     */
    public function count(): int
    {
        return $this->query()->count();
    }

    /**
     * Check if entity exists
     */
    public function exists(int|string $id): bool
    {
        $pk = $this->metadata->getPrimaryKey();
        
        return $this->query()
            ->where($pk, '=', $id)
            ->exists();
    }

    /**
     * Create new entity instance
     */
    public function create(array $data = []): object
    {
        $entity = new $this->entityClass();
        
        foreach ($data as $property => $value) {
            $setter = 'set' . ucfirst($property);
            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
            }
        }
        
        return $entity;
    }

    /**
     * Get paginated results
     */
    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $total = $this->count();
        $offset = ($page - 1) * $perPage;
        
        $results = $this->query()
            ->limit($perPage)
            ->offset($offset)
            ->get();
        
        $entities = array_map(
            fn($row) => EntityManager::map($this->entityClass, $row),
            $results
        );
        
        return [
            'data' => $entities,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Get entity metadata
     */
    public function getMetadata(): EntityMetadata
    {
        return $this->metadata;
    }

    /**
     * Get table name
     */
    public function getTable(): string
    {
        return $this->metadata->table;
    }
}

/**
 * Entity trait for models
 */
trait EntityTrait
{
    /**
     * Get repository for this entity
     */
    public static function repository(?string $connection = null): Repository
    {
        return new Repository(static::class, $connection);
    }

    /**
     * Get table name
     */
    public static function getTable(): string
    {
        return EntityManager::getTable(static::class);
    }

    /**
     * Get primary key column
     */
    public static function getPrimaryKey(): ?string
    {
        return EntityManager::getPrimaryKey(static::class);
    }

    /**
     * Find all entities
     */
    public static function all(): array
    {
        return self::repository()->findAll();
    }

    /**
     * Find entity by ID
     */
    public static function find(int|string $id): ?object
    {
        return self::repository()->find($id);
    }

    /**
     * Find by criteria
     */
    public static function findBy(array $criteria): array
    {
        return self::repository()->findBy($criteria);
    }

    /**
     * Find one by criteria
     */
    public static function findOneBy(array $criteria): ?object
    {
        return self::repository()->findOneBy($criteria);
    }

    /**
     * Save entity
     */
    public function save(): int|string
    {
        return self::repository()->save($this);
    }

    /**
     * Delete entity
     */
    public function delete(): int
    {
        return self::repository()->delete($this);
    }
}
