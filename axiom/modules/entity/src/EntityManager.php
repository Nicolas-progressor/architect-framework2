<?php

declare(strict_types=1);

namespace Axiom\Entity;

use Axiom\Entity\Annotation\Entity;
use Axiom\Entity\Annotation\Column;
use Axiom\Entity\Annotation\Id;
use Axiom\Entity\Annotation\OneToMany;
use Axiom\Entity\Annotation\ManyToOne;
use Axiom\Entity\Annotation\ManyToMany;
use Axiom\Entity\Annotation\Transient;
use ReflectionClass;
use ReflectionProperty;

/**
 * Entity metadata
 */
class EntityMetadata
{
    public string $class;
    public string $table;
    public ?string $connection = null;
    
    /** @var array<string, ColumnMetadata> */
    public array $columns = [];
    
    /** @var array<string, PropertyMetadata> */
    public array $properties = [];
    
    public ?string $idProperty = null;
    public ?string $idColumn = null;
    
    /** @var array<string, RelationMetadata> */
    public array $relations = [];

    public function getPrimaryKey(): ?string
    {
        return $this->idColumn;
    }

    public function getPrimaryProperty(): ?string
    {
        return $this->idProperty;
    }
}

/**
 * Column metadata
 */
class ColumnMetadata
{
    public string $property;
    public string $column;
    public string $type;
    public ?int $length = null;
    public ?int $precision = null;
    public ?int $scale = null;
    public bool $nullable = false;
    public mixed $default = null;
    public bool $unique = false;
    public bool $autoIncrement = false;
    public ?string $comment = null;
}

/**
 * Property metadata
 */
class PropertyMetadata
{
    public string $name;
    public ?ColumnMetadata $column = null;
    public bool $transient = false;
    public ?RelationMetadata $relation = null;
}

/**
 * Relation metadata
 */
class RelationMetadata
{
    public string $type; // oneToMany, manyToOne, manyToMany
    public string $targetEntity;
    public ?string $mappedBy = null;
    public ?string $inversedBy = null;
    public ?string $pivotTable = null;
    public ?string $localKey = null;
    public ?string $foreignKey = null;
    public ?string $joinColumn = null;
    public ?string $referencedColumnName = null;
    public bool $cascade = false;
}

/**
 * Entity Manager - manages entity metadata and mapping
 */
class EntityManager
{
    /** @var array<string, EntityMetadata> */
    private static array $metadata = [];

    /** @var array<string, object> */
    private static array $repositories = [];

    /**
     * Get metadata for entity class
     */
    public static function getMetadata(string $class): EntityMetadata
    {
        if (isset(self::$metadata[$class])) {
            return self::$metadata[$class];
        }

        $metadata = self::loadMetadata($class);
        self::$metadata[$class] = $metadata;

        return $metadata;
    }

    /**
     * Load metadata from entity class
     */
    private static function loadMetadata(string $class): EntityMetadata
    {
        $reflection = new ReflectionClass($class);
        
        $metadata = new EntityMetadata();
        $metadata->class = $class;

        // Get entity annotation
        $entityAttr = $reflection->getAttributes(Entity::class)[0] ?? null;
        if ($entityAttr) {
            /** @var Entity $entity */
            $entity = $entityAttr->newInstance();
            $metadata->table = $entity->table ?? self::deriveTableName($class);
            $metadata->connection = $entity->connection;
        } else {
            $metadata->table = self::deriveTableName($class);
        }

        // Process properties
        foreach ($reflection->getProperties() as $property) {
            $propMeta = new PropertyMetadata();
            $propMeta->name = $property->getName();

            // Check for transient
            if (!empty($property->getAttributes(Transient::class))) {
                $propMeta->transient = true;
                $metadata->properties[$propMeta->name] = $propMeta;
                continue;
            }

            // Check for Id
            $idAttr = $property->getAttributes(Id::class)[0] ?? null;
            if ($idAttr) {
                /** @var Id $id */
                $id = $idAttr->newInstance();
                $metadata->idProperty = $property->getName();
                $metadata->idColumn = $id->column ?? $property->getName();
            }

            // Check for Column
            $columnAttr = $property->getAttributes(Column::class)[0] ?? null;
            if ($columnAttr) {
                /** @var Column $column */
                $column = $columnAttr->newInstance();
                
                $colMeta = new ColumnMetadata();
                $colMeta->property = $property->getName();
                $colMeta->column = $column->name ?? $property->getName();
                $colMeta->type = $column->type ?? self::guessType($property);
                $colMeta->length = $column->length;
                $colMeta->precision = $column->precision;
                $colMeta->scale = $column->scale;
                $colMeta->nullable = $column->nullable;
                $colMeta->default = $column->default;
                $colMeta->unique = $column->unique;
                $colMeta->autoIncrement = $column->autoIncrement;
                $colMeta->comment = $column->comment;

                $propMeta->column = $colMeta;
                $metadata->columns[$colMeta->column] = $colMeta;
            }

            // Check for OneToMany
            $oneToManyAttr = $property->getAttributes(OneToMany::class)[0] ?? null;
            if ($oneToManyAttr) {
                /** @var OneToMany $relation */
                $relation = $oneToManyAttr->newInstance();
                
                $relMeta = new RelationMetadata();
                $relMeta->type = 'oneToMany';
                $relMeta->targetEntity = $relation->targetEntity;
                $relMeta->mappedBy = $relation->mappedBy;
                $relMeta->cascade = $relation->cascade;

                $propMeta->relation = $relMeta;
                $metadata->relations[$property->getName()] = $relMeta;
            }

            // Check for ManyToOne
            $manyToOneAttr = $property->getAttributes(ManyToOne::class)[0] ?? null;
            if ($manyToOneAttr) {
                /** @var ManyToOne $relation */
                $relation = $manyToOneAttr->newInstance();
                
                $relMeta = new RelationMetadata();
                $relMeta->type = 'manyToOne';
                $relMeta->targetEntity = $relation->targetEntity;
                $relMeta->inversedBy = $relation->inversedBy;
                $relMeta->joinColumn = $relation->joinColumn;
                $relMeta->referencedColumnName = $relation->referencedColumnName;
                $relMeta->cascade = $relation->cascade;

                $propMeta->relation = $relMeta;
                $metadata->relations[$property->getName()] = $relMeta;
            }

            // Check for ManyToMany
            $manyToManyAttr = $property->getAttributes(ManyToMany::class)[0] ?? null;
            if ($manyToManyAttr) {
                /** @var ManyToMany $relation */
                $relation = $manyToManyAttr->newInstance();
                
                $relMeta = new RelationMetadata();
                $relMeta->type = 'manyToMany';
                $relMeta->targetEntity = $relation->targetEntity;
                $relMeta->mappedBy = $relation->mappedBy;
                $relMeta->inversedBy = $relation->inversedBy;
                $relMeta->pivotTable = $relation->pivotTable;
                $relMeta->localKey = $relation->localKey;
                $relMeta->foreignKey = $relation->foreignKey;
                $relMeta->cascade = $relation->cascade;

                $propMeta->relation = $relMeta;
                $metadata->relations[$property->getName()] = $relMeta;
            }

            $metadata->properties[$propMeta->name] = $propMeta;
        }

        return $metadata;
    }

    /**
     * Derive table name from class name
     */
    private static function deriveTableName(string $class): string
    {
        $name = basename(str_replace('\\', '/', $class));
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }

    /**
     * Guess PHP type from reflection
     */
    private static function guessType(ReflectionProperty $property): string
    {
        $type = $property->getType();
        
        if ($type === null) {
            return 'string';
        }

        $typeName = $type->getName();

        return match ($typeName) {
            'int', 'integer' => 'integer',
            'float', 'double' => 'float',
            'bool', 'boolean' => 'boolean',
            'array' => 'json',
            \DateTime::class, \DateTimeImmutable::class => 'datetime',
            default => 'string',
        };
    }

    /**
     * Map database row to entity
     */
    public static function map(string $class, array $data): object
    {
        $metadata = self::getMetadata($class);
        $entity = new $class();

        foreach ($metadata->properties as $property => $propMeta) {
            if ($propMeta->transient) {
                continue;
            }

            // Handle column mapping
            if ($propMeta->column !== null) {
                $columnName = $propMeta->column->column;
                
                if (!array_key_exists($columnName, $data)) {
                    continue;
                }

                $value = $data[$columnName];
                $value = self::convertValue($value, $propMeta->column->type);

                $setter = 'set' . ucfirst($property);
                if (method_exists($entity, $setter)) {
                    $entity->$setter($value);
                } else {
                    $entity->$property = $value;
                }
            }
        }

        return $entity;
    }

    /**
     * Map entity to database row
     */
    public static function toArray(object $entity): array
    {
        $class = get_class($entity);
        $metadata = self::getMetadata($class);
        
        $data = [];
        $id = null;

        foreach ($metadata->properties as $property => $propMeta) {
            if ($propMeta->transient) {
                continue;
            }

            // Get value
            $getter = 'get' . ucfirst($property);
            if (method_exists($entity, $getter)) {
                $value = $entity->$getter();
            } else {
                $value = $entity->$property ?? null;
            }

            // Handle column mapping
            if ($propMeta->column !== null) {
                $columnName = $propMeta->column->column;
                $data[$columnName] = self::convertToDb($value, $propMeta->column->type);
                
                if ($property === $metadata->idProperty) {
                    $id = $value;
                }
            }
        }

        return ['data' => $data, 'id' => $id];
    }

    /**
     * Convert value from database to PHP
     */
    private static function convertValue(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean', 'bool' => (bool) $value,
            'integer', 'int' => (int) $value,
            'float', 'double' => (float) $value,
            'datetime', 'date', 'timestamp' => $value instanceof \DateTime ? $value : new \DateTime($value),
            'json' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    /**
     * Convert PHP value to database
     */
    private static function convertToDb(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean', 'bool' => $value ? 1 : 0,
            'datetime', 'date', 'timestamp' => $value instanceof \DateTime ? $value->format('Y-m-d H:i:s') : $value,
            'json' => is_array($value) ? json_encode($value) : $value,
            default => $value,
        };
    }

    /**
     * Clear metadata cache
     */
    public static function clearMetadata(): void
    {
        self::$metadata = [];
        self::$repositories = [];
    }

    /**
     * Get table name for entity
     */
    public static function getTable(string $class): string
    {
        return self::getMetadata($class)->table;
    }

    /**
     * Get primary key column
     */
    public static function getPrimaryKey(string $class): ?string
    {
        return self::getMetadata($class)->getPrimaryKey();
    }
}
