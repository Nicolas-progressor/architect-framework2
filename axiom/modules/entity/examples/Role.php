<?php

declare(strict_types=1);

namespace App\Entity;

use Axiom\Entity\Annotation as ORM;

/**
 * Role entity for many-to-many relationship
 */
#[ORM\Entity(table: 'roles')]
class Role
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer', autoIncrement: true)]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 50, unique: true)]
    private string $name = '';

    #[ORM\Column(name: 'slug', type: 'string', length: 50, unique: true)]
    private string $slug = '';

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTime $createdAt = null;

    // Many-to-Many relationship
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'roles', pivotTable: 'user_roles', localKey: 'role_id', foreignKey: 'user_id')]
    private array $users = [];

    // ========== Getters and Setters ==========

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUsers(): array
    {
        return $this->users;
    }

    public function setUsers(array $users): void
    {
        $this->users = $users;
    }
}
