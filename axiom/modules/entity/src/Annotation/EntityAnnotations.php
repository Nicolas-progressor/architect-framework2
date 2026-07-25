<?php

declare(strict_types=1);

namespace Axiom\Entity\Annotation;

use Attribute;

/**
 * Entity attribute - marks a class as an ORM entity
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Entity
{
    public function __construct(
        public ?string $table = null,
        public ?string $connection = null
    ) {}
}

/**
 * Column attribute - maps property to database column
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public ?string $name = null,
        public ?string $type = null,
        public ?int $length = null,
        public ?int $precision = null,
        public ?int $scale = null,
        public bool $nullable = false,
        public mixed $default = null,
        public bool $unique = false,
        public bool $autoIncrement = false,
        public ?string $comment = null
    ) {}
}

/**
 * Id attribute - marks property as primary key
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Id
{
    public function __construct(
        public ?string $column = null,
        public ?string $type = null
    ) {}
}

/**
 * OneToMany relationship
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class OneToMany
{
    public function __construct(
        public string $targetEntity,
        public ?string $mappedBy = null,
        public ?string $orderBy = null,
        public bool $cascade = false
    ) {}
}

/**
 * ManyToOne relationship
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ManyToOne
{
    public function __construct(
        public string $targetEntity,
        public ?string $inversedBy = null,
        public ?string $joinColumn = null,
        public ?string $referencedColumnName = null,
        public bool $cascade = false
    ) {}
}

/**
 * ManyToMany relationship
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ManyToMany
{
    public function __construct(
        public string $targetEntity,
        public ?string $mappedBy = null,
        public ?string $inversedBy = null,
        public ?string $pivotTable = null,
        public ?string $localKey = null,
        public ?string $foreignKey = null,
        public bool $cascade = false
    ) {}
}

/**
 * Join column for relationships
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class JoinColumn
{
    public function __construct(
        public ?string $name = null,
        public ?string $referencedColumnName = null,
        public ?string $onDelete = null,
        public ?string $onUpdate = null,
        public bool $nullable = true
    ) {}
}

/**
 * Generated value for auto-increment
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class GeneratedValue
{
    public function __construct(
        public ?string $strategy = 'AUTO'
    ) {}
}

/**
 * Transient - exclude from persistence
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Transient
{
    // No properties needed - just marks property as transient
}

/**
 * Version - for optimistic locking
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Version
{
    public function __construct(
        public ?string $column = null
    ) {}
}
